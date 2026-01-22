<?php
/**
 * Login/Register Page - Passwordless OTP or Google OAuth
 */

$error = $error ?? null;
$email = $email ?? '';
$step = $step ?? 'email'; // 'email', 'otp', 'username'
?>

<section class="container" style="min-height: 100vh; display: flex; align-items: center; justify-content: center;">
    <div class="card" style="width: 100%; max-width: 400px;">
        <!-- Logo -->
        <div class="text-center mb-xl">
            <h1 class="brand-name" style="font-size: 1.5rem;">TiredOfDoinTM</h1>
            <p class="subtitle mt-sm">Sign in to continue</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error mb-lg" style="padding: 1rem; background: rgba(248, 113, 113, 0.1); border-radius: var(--radius-md); color: var(--error);">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($step === 'email'): ?>
            <!-- Step 1: Enter Email -->
            <form method="POST" action="/auth/request-otp" id="email-form">
                <?= \App\Utils\View::csrf() ?>

                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-input"
                        placeholder="your@email.com"
                        value="<?= htmlspecialchars($email) ?>"
                        required
                        autofocus
                    >
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Continue with Email
                </button>
            </form>

            <div class="text-center mt-lg">
                <span class="text-muted text-sm">or</span>
            </div>

            <!-- Google OAuth -->
            <a href="/auth/google" class="btn btn-secondary mt-lg" style="width: 100%;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                </svg>
                Continue with Google
            </a>

        <?php elseif ($step === 'otp'): ?>
            <!-- Step 2: Enter OTP -->
            <form method="POST" action="/auth/verify-otp" id="otp-form">
                <?= \App\Utils\View::csrf() ?>
                <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">

                <p class="text-secondary text-center mb-lg">
                    We sent a code to<br>
                    <strong class="text-primary"><?= htmlspecialchars($email) ?></strong>
                </p>

                <div class="form-group">
                    <label class="form-label text-center" for="otp">Verification Code</label>
                    <div class="flex gap-sm justify-center">
                        <?php for ($i = 0; $i < 6; $i++): ?>
                            <input
                                type="text"
                                class="otp-input form-input"
                                maxlength="1"
                                pattern="[A-Za-z0-9]"
                                data-index="<?= $i ?>"
                                <?= $i === 0 ? 'autofocus' : '' ?>
                            >
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" name="otp" id="otp-hidden">
                </div>

                <button type="submit" class="btn btn-primary mt-lg" style="width: 100%;">
                    Verify Code
                </button>

                <p class="text-center mt-lg">
                    <a href="/login" class="text-violet text-sm">← Use a different email</a>
                </p>
            </form>

            <script>
            // OTP input handling
            document.querySelectorAll('.otp-input').forEach((input, index, inputs) => {
                input.addEventListener('input', (e) => {
                    const value = e.target.value.toUpperCase();
                    e.target.value = value;

                    if (value && index < inputs.length - 1) {
                        inputs[index + 1].focus();
                    }

                    // Update hidden field
                    document.getElementById('otp-hidden').value =
                        Array.from(inputs).map(i => i.value).join('');
                });

                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Backspace' && !e.target.value && index > 0) {
                        inputs[index - 1].focus();
                    }
                });

                // Handle paste
                input.addEventListener('paste', (e) => {
                    e.preventDefault();
                    const paste = (e.clipboardData || window.clipboardData)
                        .getData('text')
                        .toUpperCase()
                        .replace(/[^A-Z0-9]/g, '');

                    paste.split('').forEach((char, i) => {
                        if (inputs[i]) inputs[i].value = char;
                    });

                    document.getElementById('otp-hidden').value = paste.substring(0, 6);
                    inputs[Math.min(paste.length, inputs.length - 1)].focus();
                });
            });
            </script>

        <?php elseif ($step === 'username'): ?>
            <!-- Step 3: Choose Username (new users) -->
            <form method="POST" action="/auth/set-username" id="username-form">
                <?= \App\Utils\View::csrf() ?>
                <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">

                <p class="text-secondary text-center mb-lg">
                    Almost done! Choose a username.
                </p>

                <div class="form-group">
                    <label class="form-label" for="username">Username</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-input"
                        placeholder="yourname"
                        pattern="[a-zA-Z0-9_]{3,20}"
                        minlength="3"
                        maxlength="20"
                        required
                        autofocus
                    >
                    <p class="text-muted text-sm mt-sm">3-20 characters, letters, numbers, underscores only</p>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Complete Sign Up
                </button>
            </form>
        <?php endif; ?>

        <!-- Terms -->
        <p class="text-muted text-sm text-center mt-xl">
            By signing in, you agree to our Terms of Service and Privacy Policy.
        </p>
    </div>
</section>
