<?php
/**
 * Terms of Service Page - Required for Google OAuth Verification
 */
?>

<section class="legal-page container" style="padding-top: 120px; padding-bottom: 80px;">
    <div class="legal-content" style="max-width: 800px; margin: 0 auto;">
        <h1 class="mb-lg">Terms of Service</h1>
        <p class="text-muted mb-xl">Last updated: <?= date('F j, Y') ?></p>

        <div class="legal-section">
            <h2>1. Agreement to Terms</h2>
            <p>By accessing or using TiredOfDoinTM's website (tiredprod.com) and photography services, you agree to be bound by these Terms of Service. If you do not agree to these terms, please do not use our services.</p>
        </div>

        <div class="legal-section">
            <h2>2. Services Provided</h2>
            <p>TiredOfDoinTM provides professional photography services including:</p>
            <ul>
                <li>Portrait and lifestyle photography</li>
                <li>Product photography</li>
                <li>Group and event photography</li>
                <li>Photo editing and delivery</li>
                <li>Online gallery access</li>
            </ul>
        </div>

        <div class="legal-section">
            <h2>3. Booking and Payments</h2>
            <h3>3.1 Booking Process</h3>
            <p>All bookings must be made through our website. A booking is confirmed only after:</p>
            <ul>
                <li>Completion of the booking form</li>
                <li>Signing of the photography contract</li>
                <li>Payment of the required deposit (50%)</li>
            </ul>

            <h3>3.2 Payment Terms</h3>
            <ul>
                <li>A 50% deposit is required to secure your booking</li>
                <li>The remaining balance is due before or on the session date</li>
                <li>All payments are processed securely through Stripe</li>
                <li>Prices are in USD unless otherwise specified</li>
            </ul>

            <h3>3.3 Cancellation Policy</h3>
            <ul>
                <li><strong>7+ days before session:</strong> Full deposit refund</li>
                <li><strong>3-6 days before session:</strong> 50% deposit refund</li>
                <li><strong>Less than 3 days:</strong> No refund</li>
                <li>Rescheduling is available with at least 48 hours notice</li>
            </ul>
        </div>

        <div class="legal-section">
            <h2>4. Image Rights and Usage</h2>
            <h3>4.1 Client Rights</h3>
            <p>Upon full payment, clients receive:</p>
            <ul>
                <li>Personal, non-commercial use license for all delivered images</li>
                <li>Right to print and share images for personal purposes</li>
                <li>Digital copies in high resolution</li>
            </ul>

            <h3>4.2 Photographer Rights</h3>
            <p>TiredOfDoinTM retains:</p>
            <ul>
                <li>Copyright ownership of all images</li>
                <li>Right to use images for portfolio, marketing, and social media</li>
                <li>Right to enter images in photography competitions</li>
            </ul>
            <p>If you prefer your images not be used for marketing, please inform us in writing before the session.</p>
        </div>

        <div class="legal-section">
            <h2>5. User Accounts</h2>
            <p>When creating an account, you agree to:</p>
            <ul>
                <li>Provide accurate and complete information</li>
                <li>Maintain the security of your account credentials</li>
                <li>Notify us immediately of any unauthorized access</li>
                <li>Accept responsibility for all activities under your account</li>
            </ul>
        </div>

        <div class="legal-section">
            <h2>6. Gallery Access</h2>
            <ul>
                <li>Private galleries are password-protected and intended only for authorized viewers</li>
                <li>Sharing gallery links or passwords without permission is prohibited</li>
                <li>Gallery access may be time-limited as specified in your contract</li>
            </ul>
        </div>

        <div class="legal-section">
            <h2>7. Prohibited Conduct</h2>
            <p>You agree not to:</p>
            <ul>
                <li>Use our services for any unlawful purpose</li>
                <li>Impersonate any person or entity</li>
                <li>Interfere with the proper functioning of our website</li>
                <li>Attempt to gain unauthorized access to our systems</li>
                <li>Redistribute or resell images without permission</li>
            </ul>
        </div>

        <div class="legal-section">
            <h2>8. Limitation of Liability</h2>
            <p>To the maximum extent permitted by law:</p>
            <ul>
                <li>TiredOfDoinTM is not liable for indirect, incidental, or consequential damages</li>
                <li>Total liability shall not exceed the amount paid for services</li>
                <li>We are not responsible for data loss due to circumstances beyond our control</li>
            </ul>
        </div>

        <div class="legal-section">
            <h2>9. Indemnification</h2>
            <p>You agree to indemnify and hold harmless TiredOfDoinTM from any claims, damages, or expenses arising from your use of our services or violation of these terms.</p>
        </div>

        <div class="legal-section">
            <h2>10. Changes to Terms</h2>
            <p>We reserve the right to modify these terms at any time. Continued use of our services after changes constitutes acceptance of the new terms.</p>
        </div>

        <div class="legal-section">
            <h2>11. Governing Law</h2>
            <p>These terms are governed by the laws of the State of California, United States, without regard to conflict of law principles.</p>
        </div>

        <div class="legal-section">
            <h2>12. Contact Information</h2>
            <p>For questions about these Terms of Service:</p>
            <ul>
                <li>Email: legal@tiredprod.com</li>
                <li>Website: <a href="https://tiredprod.com/contact">tiredprod.com/contact</a></li>
            </ul>
        </div>
    </div>
</section>

<style>
.legal-page {
    color: var(--text-primary);
}
.legal-section {
    margin-bottom: 2.5rem;
}
.legal-section h2 {
    font-size: 1.5rem;
    margin-bottom: 1rem;
    color: var(--text-primary);
}
.legal-section h3 {
    font-size: 1.125rem;
    margin: 1.5rem 0 0.75rem;
    color: var(--text-secondary);
}
.legal-section p {
    margin-bottom: 1rem;
    line-height: 1.7;
}
.legal-section ul {
    margin: 0.5rem 0 1rem 1.5rem;
    color: var(--text-secondary);
}
.legal-section li {
    margin-bottom: 0.5rem;
    line-height: 1.6;
}
.legal-section a {
    color: var(--violet-400);
}
</style>
