<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PrintJob;
use App\Services\PrintJobService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Pusher\PushNotifications\PushNotifications;

class PrintJobController extends Controller
{
    public function __construct(private PrintJobService $printJobService) {}

    // List jobs — Android uses this to re-sync pending jobs after reconnect
    public function index(Request $request): JsonResponse
    {
        $jobs = PrintJob::with('order')
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->order_id, fn ($q) => $q->where('order_id', $request->order_id))
            ->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->map(fn ($job) => [
                'id'             => $job->id,
                'order_id'       => $job->order_id,
                'trigger'        => $job->trigger,
                'trigger_status' => $job->trigger_status,
                'status'         => $job->status,
                'attempts'       => $job->attempts,
                'created_at'     => $job->created_at,
                'printed_at'     => $job->printed_at,
                'receipt'        => $job->receipt_data,
            ]);

        return response()->json($jobs);
    }

    // Manually trigger a print job for an order (e.g. Reprint button)
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
        ]);

        $order = Order::with(['items.product', 'user', 'queueNumber', 'payments.tender'])
            ->findOrFail($request->order_id);

        $job = $this->printJobService->queueForNewOrder($order);

        return response()->json([
            'id'         => $job->id,
            'status'     => $job->status,
            'created_at' => $job->created_at,
        ], 201);
    }

    // Android calls this after printing to acknowledge success or failure
    public function acknowledge(Request $request, PrintJob $printJob): JsonResponse
    {
        $data = $request->validate([
            'status' => 'required|in:printed,failed',
            'reason' => 'nullable|string|max:255',
        ]);

        $printJob->update([
            'status'        => $data['status'],
            'failed_reason' => $data['status'] === 'failed' ? ($data['reason'] ?? null) : null,
            'printed_at'    => $data['status'] === 'printed' ? now() : null,
        ]);

        return response()->json(['id' => $printJob->id, 'status' => $printJob->status]);
    }

    // Broadcast a test receipt over Pusher Channels (WebSocket — primary delivery path)
    public function testChannels(Request $request): JsonResponse
    {
        $data = $request->validate([
            'channel' => 'required|string|max:100',
        ]);

        $key    = config('broadcasting.connections.pusher.key');
        $secret = config('broadcasting.connections.pusher.secret');
        $appId  = config('broadcasting.connections.pusher.app_id');

        if (! $key || ! $secret || ! $appId) {
            return response()->json([
                'ok'      => false,
                'message' => 'PUSHER_APP_KEY, PUSHER_APP_SECRET, or PUSHER_APP_ID is missing in .env.',
            ], 422);
        }

        try {
            $payload = [
                'store'   => ['name' => config('app.name'), 'address' => null, 'phone' => null, 'footer' => 'Test receipt — ignore'],
                'receipt' => ['number' => 'TEST-' . now()->format('His'), 'date' => now()->setTimezone('Asia/Manila')->format('M d, Y h:i A'), 'cashier' => auth()->user()?->name],
                'items'   => [['name' => 'Test Item', 'qty' => 1.0, 'price' => 1.00, 'total' => 1.00]],
                'totals'  => ['subtotal' => 1.00, 'tax' => 0.00, 'discount' => 0.00, 'total' => 1.00],
                'payment' => null,
            ];

            // Directly use the Pusher driver (bypasses default driver check)
            $pusher = new \Pusher\Pusher($key, $secret, $appId, [
                'cluster'  => config('broadcasting.connections.pusher.options.cluster', 'ap1'),
                'useTLS'   => true,
            ]);

            // Single event only — Android binds to both names, so sending
            // both would cause the receipt to print twice.
            $pusher->trigger($data['channel'], 'print', ['receipt' => $payload]);

            return response()->json([
                'ok'      => true,
                'message' => "Test receipt sent to channel \"{$data['channel']}\" (event: \"print\").",
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok'      => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    // Send a test push notification via Pusher Beams
    public function testNotification(Request $request): JsonResponse
    {
        $data = $request->validate([
            'interest' => 'required|string|max:100',
            'title'    => 'required|string|max:100',
            'body'     => 'required|string|max:255',
        ]);

        $instanceId = config('broadcasting.beams.instance_id');
        $secretKey  = config('broadcasting.beams.secret_key');

        if (! $instanceId || ! $secretKey) {
            return response()->json([
                'ok'      => false,
                'message' => 'PUSHER_BEAMS_INSTANCE_ID or PUSHER_BEAMS_SECRET_KEY is not configured.',
            ], 422);
        }

        try {
            $beams = new PushNotifications([
                'instanceId' => $instanceId,
                'secretKey'  => $secretKey,
            ]);

            $beams->publishToInterests(
                [$data['interest']],
                [
                    'fcm' => [
                        'notification' => [
                            'title' => $data['title'],
                            'body'  => $data['body'],
                        ],
                    ],
                    'web' => [
                        'notification' => [
                            'title' => $data['title'],
                            'body'  => $data['body'],
                        ],
                    ],
                    'apns' => [
                        'aps' => [
                            'alert' => [
                                'title' => $data['title'],
                                'body'  => $data['body'],
                            ],
                        ],
                    ],
                ]
            );

            return response()->json([
                'ok'      => true,
                'message' => "Notification sent to interest \"{$data['interest']}\".",
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok'      => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    // Retry a failed or stuck job — re-sends the Beams push notification
    public function retry(PrintJob $printJob): JsonResponse
    {
        if (! in_array($printJob->status, ['failed', 'sent'])) {
            return response()->json(['message' => 'Only failed or stuck jobs can be retried.'], 422);
        }

        $order = $printJob->order->load(['items.product', 'user', 'queueNumber']);
        $this->printJobService->queueForStatusChange($order, $printJob->trigger_status ?? 'retry');

        return response()->json(['id' => $printJob->id, 'status' => 'sent']);
    }
}
