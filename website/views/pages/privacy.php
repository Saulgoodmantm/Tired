<?php
/**
 * Privacy Policy Page - Required for Google OAuth Verification
 */
?>

<section class="legal-page container" style="padding-top: 120px; padding-bottom: 80px;">
    <div class="legal-content" style="max-width: 800px; margin: 0 auto;">
        <h1 class="mb-lg">Privacy Policy</h1>
        <p class="text-muted mb-xl">Last updated: <?= date('F j, Y') ?></p>

        <div class="legal-section">
            <h2>1. Introduction</h2>
            <p>TiredOfDoinTM ("we," "our," or "us") respects your privacy and is committed to protecting your personal data. This privacy policy explains how we collect, use, disclose, and safeguard your information when you visit our website tiredprod.com or use our photography services.</p>
        </div>

        <div class="legal-section">
            <h2>2. Information We Collect</h2>
            <h3>Personal Information</h3>
            <p>We may collect personal information that you voluntarily provide, including:</p>
            <ul>
                <li>Name and contact information (email, phone number)</li>
                <li>Billing and payment information</li>
                <li>Google account information (when using Google Sign-In)</li>
                <li>Booking preferences and session details</li>
                <li>Photos and images from your sessions</li>
            </ul>

            <h3>Automatically Collected Information</h3>
            <p>When you visit our website, we may automatically collect:</p>
            <ul>
                <li>IP address and browser type</li>
                <li>Device information</li>
                <li>Pages visited and time spent</li>
                <li>Cookies and similar tracking technologies</li>
            </ul>
        </div>

        <div class="legal-section">
            <h2>3. How We Use Your Information</h2>
            <p>We use your information to:</p>
            <ul>
                <li>Process bookings and provide photography services</li>
                <li>Communicate with you about your sessions</li>
                <li>Process payments through Stripe</li>
                <li>Send booking confirmations and updates</li>
                <li>Improve our website and services</li>
                <li>Comply with legal obligations</li>
            </ul>
        </div>

        <div class="legal-section">
            <h2>4. Google OAuth</h2>
            <p>We use Google Sign-In to provide a convenient authentication option. When you sign in with Google, we receive:</p>
            <ul>
                <li>Your Google account email address</li>
                <li>Your name and profile picture</li>
                <li>A unique identifier for your Google account</li>
            </ul>
            <p>We do not access your Google Drive, Gmail, or other Google services. You can revoke our access at any time through your <a href="https://myaccount.google.com/permissions" target="_blank" rel="noopener">Google Account settings</a>.</p>
        </div>

        <div class="legal-section">
            <h2>5. Data Sharing and Disclosure</h2>
            <p>We do not sell your personal information. We may share your data with:</p>
            <ul>
                <li><strong>Stripe:</strong> For secure payment processing</li>
                <li><strong>Cloudflare:</strong> For content delivery and security</li>
                <li><strong>Google:</strong> For authentication services</li>
            </ul>
            <p>We may also disclose information when required by law or to protect our rights.</p>
        </div>

        <div class="legal-section">
            <h2>6. Data Security</h2>
            <p>We implement appropriate security measures including:</p>
            <ul>
                <li>SSL/TLS encryption for all data transmission</li>
                <li>Secure password hashing</li>
                <li>Regular security updates</li>
                <li>Access controls and authentication</li>
            </ul>
        </div>

        <div class="legal-section">
            <h2>7. Your Rights</h2>
            <p>You have the right to:</p>
            <ul>
                <li>Access your personal data</li>
                <li>Correct inaccurate data</li>
                <li>Request deletion of your data</li>
                <li>Withdraw consent for data processing</li>
                <li>Export your data</li>
            </ul>
            <p>To exercise these rights, contact us at privacy@tiredprod.com</p>
        </div>

        <div class="legal-section">
            <h2>8. Cookies</h2>
            <p>We use essential cookies for:</p>
            <ul>
                <li>Session management and authentication</li>
                <li>Gate access verification</li>
                <li>Security and fraud prevention</li>
            </ul>
        </div>

        <div class="legal-section">
            <h2>9. Children's Privacy</h2>
            <p>Our services are not directed to individuals under 18. We do not knowingly collect personal information from children without parental consent.</p>
        </div>

        <div class="legal-section">
            <h2>10. Changes to This Policy</h2>
            <p>We may update this privacy policy periodically. We will notify you of significant changes by posting the new policy on this page with an updated revision date.</p>
        </div>

        <div class="legal-section">
            <h2>11. Contact Us</h2>
            <p>If you have questions about this privacy policy, please contact us:</p>
            <ul>
                <li>Email: privacy@tiredprod.com</li>
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
