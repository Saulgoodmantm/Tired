<?php
/**
 * Contact Page
 */

$config = require __DIR__ . '/../../config/app.php';
?>

<section class="container" style="padding-top: 100px; padding-bottom: 4rem;">
    <div class="text-center mb-xl">
        <h1 class="text-2xl mb-md">Get in Touch</h1>
        <p class="text-secondary">Have questions? Let's talk about your next project.</p>
    </div>

    <div class="contact-grid">
        <!-- Contact Form -->
        <div class="card">
            <h3 class="mb-lg">Send a Message</h3>

            <form action="/api/contact" method="POST" id="contact-form">
                <?= \App\Utils\View::csrf() ?>

                <div class="form-group">
                    <label class="form-label" for="name">Name *</label>
                    <input type="text" id="name" name="name" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Email *</label>
                    <input type="email" id="email" name="email" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="subject">Subject *</label>
                    <select id="subject" name="subject" class="form-select" required>
                        <option value="">Select a topic</option>
                        <option value="booking">Booking Inquiry</option>
                        <option value="pricing">Pricing Question</option>
                        <option value="collaboration">Collaboration</option>
                        <option value="model">Model Application</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="message">Message *</label>
                    <textarea id="message" name="message" class="form-textarea" rows="5" required></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Send Message
                </button>
            </form>
        </div>

        <!-- Contact Info -->
        <div class="contact-info">
            <div class="card mb-lg">
                <h3 class="mb-lg">Contact Info</h3>

                <div class="contact-item">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                    <a href="mailto:contact@tiredofdointm.com">contact@tiredofdointm.com</a>
                </div>

                <div class="contact-item">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                        <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                        <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                    </svg>
                    <a href="<?= htmlspecialchars($config['social']['instagram']) ?>" target="_blank">@tiredofdointm</a>
                </div>

                <div class="contact-item">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                        <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                        <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                    </svg>
                    <a href="<?= htmlspecialchars($config['social']['instagram2']) ?>" target="_blank">@tiredflics</a>
                </div>

                <div class="contact-item">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"></path>
                        <polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"></polygon>
                    </svg>
                    <a href="<?= htmlspecialchars($config['social']['tiktok']) ?>" target="_blank">@tiredofdointm</a>
                </div>
            </div>

            <div class="card">
                <h3 class="mb-lg">Response Time</h3>
                <p class="text-secondary mb-md">
                    I typically respond within 24-48 hours. For urgent inquiries, please reach out via Instagram DM.
                </p>
                <p class="text-muted text-sm">
                    Based in [Your City] • Available for travel worldwide
                </p>
            </div>
        </div>
    </div>
</section>

<style>
.contact-grid {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: var(--space-xl);
    max-width: 1000px;
    margin: 0 auto;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: var(--space-md);
    padding: var(--space-md) 0;
    border-bottom: 1px solid var(--bg-hover);
}

.contact-item:last-child {
    border-bottom: none;
}

.contact-item svg {
    color: var(--violet-400);
    flex-shrink: 0;
}

.contact-item a {
    color: var(--text-primary);
}

.contact-item a:hover {
    color: var(--violet-400);
}

@media (max-width: 768px) {
    .contact-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
document.getElementById('contact-form')?.addEventListener('submit', async function(e) {
    e.preventDefault();

    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Sending...';

    try {
        const formData = new FormData(this);
        const response = await fetch('/api/contact', {
            method: 'POST',
            body: formData,
        });

        const data = await response.json();

        if (data.success) {
            TODT.utils.notify('Message sent successfully!', 'success');
            this.reset();
        } else {
            TODT.utils.notify(data.error || 'Failed to send message', 'error');
        }
    } catch (error) {
        TODT.utils.notify('Failed to send message', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = 'Send Message';
    }
});
</script>
