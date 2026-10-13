# API Testing Guide

Complete API endpoint testing guide with cURL examples.

## Prerequisites

1. Ensure application is running: `php artisan serve`
2. Seed database: `php artisan db:seed`
3. Get authentication token (see Authentication section)

## Base URL

```
http://localhost:8000/api/v1
```

## Authentication

### Get Token
```bash
curl -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "maria@bypassgrill.local",
    "password": "password"
  }'
```

Response:
```json
{
  "token": "your_token_here",
  "user": {
    "id": 2,
    "name": "Maria Santos",
    "email": "maria@bypassgrill.local",
    "roles": ["cashier"]
  }
}
```

Save token:
```bash
TOKEN="your_token_here"
```

Use in all protected requests:
```bash
-H "Authorization: Bearer $TOKEN"
```

---

## Public Endpoints (No Auth)

### List Categories
```bash
curl -X GET http://localhost:8000/api/v1/categories
```

### Get Single Category
```bash
curl -X GET http://localhost:8000/api/v1/categories/1
```

### List Products
```bash
curl -X GET http://localhost:8000/api/v1/products
```

### Get Single Product
```bash
curl -X GET http://localhost:8000/api/v1/products/1
```

### Get Products by Category
```bash
curl -X GET http://localhost:8000/api/v1/products/category/1
```

### Search Products
```bash
curl -X GET "http://localhost:8000/api/v1/products/search?q=burger"
```

---

## Order Endpoints (Protected)

### List Orders
```bash
curl -X GET http://localhost:8000/api/v1/orders \
  -H "Authorization: Bearer $TOKEN"
```

### Get Active Orders
```bash
curl -X GET http://localhost:8000/api/v1/orders/active \
  -H "Authorization: Bearer $TOKEN"
```

### Get Queue Orders
```bash
curl -X GET http://localhost:8000/api/v1/orders/queue \
  -H "Authorization: Bearer $TOKEN"
```

### Get Single Order
```bash
curl -X GET http://localhost:8000/api/v1/orders/1 \
  -H "Authorization: Bearer $TOKEN"
```

### Create Order (Cashier Only)
```bash
curl -X POST http://localhost:8000/api/v1/orders \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "order_type": "dine_in",
    "table_number": "5",
    "items": [
      {
        "product_id": 1,
        "quantity": 2,
        "modifiers": [1, 2]
      },
      {
        "product_id": 3,
        "quantity": 1,
        "modifiers": []
      }
    ],
    "notes": "Extra hot"
  }'
```

Response:
```json
{
  "data": {
    "id": 101,
    "order_type": "dine_in",
    "table_number": "5",
    "status": "pending",
    "queue_number": 5,
    "subtotal": "600.00",
    "discount_amount": "0.00",
    "tax_amount": "72.00",
    "total_amount": "672.00",
    "items": [...],
    "user": {...}
  }
}
```

### Update Order Status (Kitchen)
```bash
curl -X PATCH http://localhost:8000/api/v1/orders/1/status \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "preparing"
  }'
```

Valid statuses: `pending`, `preparing`, `ready`, `completed`, `cancelled`

### Cancel Order
```bash
curl -X POST http://localhost:8000/api/v1/orders/1/cancel \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "reason": "Customer requested cancellation"
  }'
```

---

## Payment Endpoints (Protected)

### Process Payment (Cashier)
```bash
curl -X POST http://localhost:8000/api/v1/payments \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "order_id": 1,
    "amount": 672.00,
    "method": "cash",
    "reference": "Receipt123"
  }'
```

Valid methods: `cash`, `card`, `e_wallet`, `check`

### Get Order Payments
```bash
curl -X GET http://localhost:8000/api/v1/orders/1/payments \
  -H "Authorization: Bearer $TOKEN"
```

### Issue Refund (Cashier)
```bash
curl -X POST http://localhost:8000/api/v1/payments/1/refund \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 100.00,
    "reason": "Customer returned items"
  }'
```

---

## Inventory Endpoints (Protected)

### List All Inventory
```bash
curl -X GET http://localhost:8000/api/v1/inventory \
  -H "Authorization: Bearer $TOKEN"
```

### Get Low Stock Items
```bash
curl -X GET http://localhost:8000/api/v1/inventory/low-stock \
  -H "Authorization: Bearer $TOKEN"
```

### Adjust Inventory (Auditor/Admin)
```bash
curl -X POST http://localhost:8000/api/v1/inventory/adjust \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "ingredient_id": 1,
    "quantity": 10,
    "type": "stock_in",
    "reference": "PO-2025-001",
    "notes": "Received from Metro Food Supplier"
  }'
```

Valid types: `stock_in`, `stock_out`, `adjustment`, `waste`

### Get Ingredient Transactions
```bash
curl -X GET http://localhost:8000/api/v1/inventory/1/transactions \
  -H "Authorization: Bearer $TOKEN"
```

---

## Report Endpoints (Protected - Auditor/Admin Only)

### Daily Sales Report
```bash
curl -X GET "http://localhost:8000/api/v1/reports/daily-sales?date=2025-05-12" \
  -H "Authorization: Bearer $TOKEN"
```

### Monthly Sales Report
```bash
curl -X GET "http://localhost:8000/api/v1/reports/monthly-sales?year=2025&month=5" \
  -H "Authorization: Bearer $TOKEN"
```

### Product Sales Report
```bash
curl -X GET "http://localhost:8000/api/v1/reports/product-sales?start_date=2025-05-01&end_date=2025-05-12" \
  -H "Authorization: Bearer $TOKEN"
```

### Inventory Valuation
```bash
curl -X GET http://localhost:8000/api/v1/reports/inventory-valuation \
  -H "Authorization: Bearer $TOKEN"
```

---

## Test Workflows

### Complete POS Workflow

1. **Cashier gets token:**
```bash
TOKEN=$(curl -s -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "maria@bypassgrill.local",
    "password": "password"
  }' | jq -r '.token')
```

2. **Browse products:**
```bash
curl -s -X GET http://localhost:8000/api/v1/products | jq '.'
```

3. **Create order:**
```bash
ORDER=$(curl -s -X POST http://localhost:8000/api/v1/orders \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "order_type": "dine_in",
    "table_number": "5",
    "items": [
      {
        "product_id": 1,
        "quantity": 2,
        "modifiers": [1, 2]
      }
    ]
  }' | jq '.data.id')
```

4. **Process payment:**
```bash
curl -s -X POST http://localhost:8000/api/v1/payments \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"order_id\": $ORDER,
    \"amount\": 672.00,
    \"method\": \"cash\",
    \"reference\": \"Receipt001\"
  }"
```

### Complete Kitchen Workflow

1. **Kitchen staff gets token:**
```bash
TOKEN=$(curl -s -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "rosa@bypassgrill.local",
    "password": "password"
  }' | jq -r '.token')
```

2. **View active orders:**
```bash
curl -s -X GET http://localhost:8000/api/v1/orders/active \
  -H "Authorization: Bearer $TOKEN" | jq '.'
```

3. **Start preparing order:**
```bash
curl -s -X PATCH http://localhost:8000/api/v1/orders/101/status \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status": "preparing"}'
```

4. **Mark ready:**
```bash
curl -s -X PATCH http://localhost:8000/api/v1/orders/101/status \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status": "ready"}'
```

### Inventory Adjustment Workflow

1. **Auditor gets token:**
```bash
TOKEN=$(curl -s -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "anna@bypassgrill.local",
    "password": "password"
  }' | jq -r '.token')
```

2. **View low stock:**
```bash
curl -s -X GET http://localhost:8000/api/v1/inventory/low-stock \
  -H "Authorization: Bearer $TOKEN" | jq '.'
```

3. **Adjust stock:**
```bash
curl -s -X POST http://localhost:8000/api/v1/inventory/adjust \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "ingredient_id": 1,
    "quantity": 50,
    "type": "stock_in",
    "reference": "PO-12345",
    "notes": "Received Ground Beef"
  }'
```

---

## Error Responses

### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

### 403 Forbidden
```json
{
  "message": "This action is unauthorized."
}
```

### 422 Validation Error
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "order_type": ["The order type field is required."],
    "items": ["The items field must be an array with at least 1 element."]
  }
}
```

### 404 Not Found
```json
{
  "message": "Not found."
}
```

---

## Using Postman

### Import Collection

Create a Postman environment with:
```json
{
  "baseUrl": "http://localhost:8000/api/v1",
  "token": "your_token_here"
}
```

Use in requests:
- URL: `{{baseUrl}}/orders`
- Header: `Authorization: Bearer {{token}}`

### Save Token Automatically

In authentication response script:
```javascript
var jsonData = pm.response.json();
pm.environment.set("token", jsonData.token);
```

---

## Common Issues

**401 Unauthorized - Token Expired/Invalid:**
- Get new token
- Ensure Authorization header is included
- Check token format: `Bearer token_value`

**422 Validation Error:**
- Check required fields
- Verify field types and formats
- Read error message for specific issues

**403 Permission Denied:**
- Verify user role has permission
- Check Spatie permissions
- Ensure role is properly assigned

**500 Internal Server Error:**
- Check `storage/logs/laravel.log`
- Ensure database is running
- Check migrations have run

---

## Performance Tips

1. **Use pagination:**
```bash
curl -X GET "http://localhost:8000/api/v1/orders?page=1&per_page=20" \
  -H "Authorization: Bearer $TOKEN"
```

2. **Filter results:**
```bash
curl -X GET "http://localhost:8000/api/v1/orders?status=pending" \
  -H "Authorization: Bearer $TOKEN"
```

3. **Use sparse fieldsets** (when implemented):
```bash
curl -X GET "http://localhost:8000/api/v1/orders?fields=id,total_amount,status" \
  -H "Authorization: Bearer $TOKEN"
```

---

## Next Steps

1. Test all endpoints with provided examples
2. Implement in your frontend (see Vue composables)
3. Setup real-time with Pusher/WebSockets
4. Add more test data as needed
5. Monitor logs for issues

For more details, see [SYSTEM_DOCUMENTATION.md](SYSTEM_DOCUMENTATION.md)
