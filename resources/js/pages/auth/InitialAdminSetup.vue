<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ArrowRight, KeyRound, LockKeyhole, ShieldCheck } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

defineOptions({
    layout: {
        title: 'Set up your administrator',
        description: 'Secure, one-time account creation for DEMO POS.',
    },
});
</script>

<template>
    <Head title="Initial administrator setup">
        <meta name="robots" content="noindex, nofollow" />
    </Head>

    <div class="space-y-6">
        <div class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900">
            <ShieldCheck class="mt-0.5 size-6 shrink-0 text-slate-700 dark:text-slate-200" aria-hidden="true" />
            <div>
                <p class="text-sm font-semibold">Protected first-time setup</p>
                <p class="mt-1 text-xs leading-5 text-muted-foreground">
                    Enter the one-time setup code configured in Laravel Forge and
                    create your first administrator. This page becomes unavailable
                    after setup is completed.
                </p>
            </div>
        </div>

        <Form
            action="/setup/admin"
            method="post"
            :reset-on-success="['password', 'password_confirmation', 'setup_token']"
            v-slot="{ errors, processing }"
            class="space-y-5"
        >
            <div class="space-y-2">
                <Label for="admin-name">Administrator name</Label>
                <Input id="admin-name" name="name" type="text" required autocomplete="name" maxlength="100" placeholder="Full name" autofocus />
                <InputError :message="errors.name" />
            </div>

            <div class="space-y-2">
                <Label for="admin-email">Administrator email</Label>
                <Input id="admin-email" name="email" type="email" required autocomplete="email" maxlength="255" placeholder="admin@example.com" />
                <InputError :message="errors.email" />
            </div>

            <div class="space-y-2">
                <Label for="admin-password">New password</Label>
                <PasswordInput id="admin-password" name="password" required autocomplete="new-password" placeholder="Create a strong password" />
                <p class="text-xs leading-5 text-muted-foreground">At least 12 characters, including uppercase, lowercase, a number, and a symbol.</p>
                <InputError :message="errors.password" />
            </div>

            <div class="space-y-2">
                <Label for="admin-password-confirmation">Confirm password</Label>
                <PasswordInput id="admin-password-confirmation" name="password_confirmation" required autocomplete="new-password" placeholder="Repeat the password" />
            </div>

            <div class="space-y-2 rounded-lg border border-slate-200 p-3 dark:border-slate-700">
                <div class="flex items-center gap-2">
                    <KeyRound class="size-4 text-muted-foreground" aria-hidden="true" />
                    <Label for="admin-setup-token">Forge initial setup code</Label>
                </div>
                <PasswordInput id="admin-setup-token" name="setup_token" required autocomplete="off" placeholder="Paste the setup code" />
                <InputError :message="errors.setup_token" />
                <p class="text-xs leading-5 text-muted-foreground">
                    The code stays on your server and is not embedded in the page.
                    It is different from your administrator password.
                </p>
            </div>

            <Button type="submit" class="w-full" :disabled="processing" data-test="create-initial-admin">
                <Spinner v-if="processing" />
                <LockKeyhole v-else class="size-4" aria-hidden="true" />
                {{ processing ? 'Creating administrator…' : 'Create administrator' }}
                <ArrowRight v-if="!processing" class="ml-1 size-4" aria-hidden="true" />
            </Button>
        </Form>
    </div>
</template>
