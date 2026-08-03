{{-- Account self-service modals (edit profile + change password) --}}
<x-ui.modal id="edit-profile-modal" title="Edit profile" subtitle="Update your display name and photo." maxWidth="max-w-md">
    <form id="edit-profile-form" class="flex min-h-0 flex-1 flex-col" novalidate>
        <div class="min-h-0 flex-1 overflow-y-auto p-4 sm:p-5 space-y-5">
            <div class="flex flex-col items-center gap-3">
                <div
                    class="relative w-24 h-24 rounded-full bg-primary text-white text-2xl font-semibold flex items-center justify-center overflow-hidden border border-border"
                    data-profile-avatar
                >
                    <img data-profile-avatar-img alt="" class="hidden w-full h-full object-cover">
                    <span data-profile-avatar-initial>U</span>
                </div>
                <div class="flex flex-wrap items-center justify-center gap-2">
                    <label
                        for="profile-photo-input"
                        class="inline-flex items-center justify-center gap-1.5 h-9 px-3 rounded-xl border border-border bg-subtle text-sm font-medium text-heading hover:bg-surface cursor-pointer transition-colors"
                    >
                        <i class="ph ph-camera text-lg text-muted" aria-hidden="true"></i>
                        Change photo
                    </label>
                    <input
                        id="profile-photo-input"
                        type="file"
                        accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp"
                        class="sr-only"
                        data-profile-photo-input
                    >
                    <button
                        type="button"
                        id="profile-photo-remove"
                        class="inline-flex items-center justify-center gap-1.5 h-9 px-3 rounded-xl border border-border text-sm font-medium text-danger hover:bg-danger-soft transition-colors hidden"
                        data-profile-photo-remove
                    >
                        <i class="ph ph-trash text-lg" aria-hidden="true"></i>
                        Remove
                    </button>
                </div>
                <p class="text-xs text-muted text-center">JPG, PNG, or WebP · max 2 MB</p>
                <p data-error="photo" class="text-sm text-danger hidden text-center"></p>
            </div>

            <div>
                <label for="profile-name" class="block text-xs font-medium text-text-secondary mb-1.5">
                    Display name
                </label>
                <input
                    id="profile-name"
                    name="name"
                    type="text"
                    maxlength="120"
                    required
                    autocomplete="name"
                    class="ui-input w-full h-11 px-3 rounded-xl border border-border bg-subtle text-sm text-text placeholder:text-faint focus:outline-none focus:border-primary transition-colors"
                    placeholder="Your name"
                >
                <p data-error="name" class="mt-1.5 text-sm text-danger hidden"></p>
            </div>

            <div>
                <label for="profile-email" class="block text-xs font-medium text-text-secondary mb-1.5">
                    Email
                </label>
                <input
                    id="profile-email"
                    type="email"
                    readonly
                    disabled
                    class="ui-input w-full h-11 px-3 rounded-xl border border-border bg-subtle text-sm text-muted cursor-not-allowed"
                >
                <p class="mt-1.5 text-xs text-muted">Email is managed by an administrator.</p>
            </div>
        </div>

        <div class="flex-shrink-0 border-t border-border p-4 sm:p-5 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
            <button
                type="button"
                class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-border text-sm font-medium text-heading hover:bg-subtle transition-colors"
                data-modal-dismiss
            >
                Cancel
            </button>
            <button
                type="submit"
                id="edit-profile-submit"
                class="inline-flex items-center justify-center h-10 px-4 rounded-xl bg-primary text-white text-sm font-medium hover:bg-primary-strong transition-colors"
            >
                Save changes
            </button>
        </div>
    </form>
</x-ui.modal>

<x-ui.modal id="change-password-modal" title="Change password" subtitle="Choose a strong password that meets policy." maxWidth="max-w-md">
    <form id="change-password-modal-form" class="flex min-h-0 flex-1 flex-col" novalidate>
        <div class="min-h-0 flex-1 overflow-y-auto p-4 sm:p-5 space-y-4">
            <p class="text-xs text-muted" data-password-modal-hint></p>

            <div>
                <label for="modal_current_password" class="block text-xs font-medium text-text-secondary mb-1.5">
                    Current password
                </label>
                <x-ui.password-input
                    name="current_password"
                    id="modal_current_password"
                    autocomplete="current-password"
                    :required="true"
                />
                <p data-error="current_password" class="mt-1.5 text-sm text-danger hidden"></p>
            </div>

            <div>
                <label for="modal_password" class="block text-xs font-medium text-text-secondary mb-1.5">
                    New password
                </label>
                <x-ui.password-input
                    name="password"
                    id="modal_password"
                    autocomplete="new-password"
                    :required="true"
                />
                <p data-error="password" class="mt-1.5 text-sm text-danger hidden"></p>
            </div>

            <div>
                <label for="modal_password_confirmation" class="block text-xs font-medium text-text-secondary mb-1.5">
                    Confirm new password
                </label>
                <x-ui.password-input
                    name="password_confirmation"
                    id="modal_password_confirmation"
                    autocomplete="new-password"
                    :required="true"
                />
                <p data-error="password_confirmation" class="mt-1.5 text-sm text-danger hidden"></p>
            </div>
        </div>

        <div class="flex-shrink-0 border-t border-border p-4 sm:p-5 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
            <button
                type="button"
                class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-border text-sm font-medium text-heading hover:bg-subtle transition-colors"
                data-modal-dismiss
            >
                Cancel
            </button>
            <button
                type="submit"
                id="change-password-modal-submit"
                class="inline-flex items-center justify-center h-10 px-4 rounded-xl bg-primary text-white text-sm font-medium hover:bg-primary-strong transition-colors"
            >
                Update password
            </button>
        </div>
    </form>
</x-ui.modal>
