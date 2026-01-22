<?php
/**
 * Booking Page - Multi-step booking flow
 */

$step = $step ?? 1;
$shootTypes = ['personal', 'product', 'group', 'event', 'misc'];
?>

<section class="container" style="padding-top: 100px; min-height: 100vh;">
    <!-- Progress Steps -->
    <div class="booking-progress flex justify-center gap-sm mb-xl">
        <?php for ($i = 1; $i <= 7; $i++): ?>
            <div class="booking-step <?= $i <= $step ? 'active' : '' ?> <?= $i < $step ? 'completed' : '' ?>">
                <?= $i ?>
            </div>
        <?php endfor; ?>
    </div>

    <div class="booking-container" style="max-width: 800px; margin: 0 auto;">

        <?php if ($step === 1): ?>
        <!-- Step 1: Date Selection -->
        <div class="booking-step-content animate-fade-in-up">
            <h2 class="text-center mb-lg">Select a Date</h2>
            <p class="text-secondary text-center mb-xl">Choose your preferred date for the photoshoot</p>

            <div class="calendar-container card" id="booking-calendar">
                <!-- Calendar will be rendered by JS -->
                <div class="calendar-header flex justify-between items-center mb-lg">
                    <button class="btn btn-ghost" id="cal-prev">&larr;</button>
                    <h3 id="cal-month-year"></h3>
                    <button class="btn btn-ghost" id="cal-next">&rarr;</button>
                </div>
                <div class="calendar-grid" id="cal-grid">
                    <!-- Days rendered by JS -->
                </div>
            </div>

            <input type="hidden" id="selected-date" name="date">
        </div>

        <?php elseif ($step === 2): ?>
        <!-- Step 2: Time Selection -->
        <div class="booking-step-content animate-fade-in-up">
            <h2 class="text-center mb-lg">Select Time</h2>
            <p class="text-secondary text-center mb-xl">
                Available times for <?= htmlspecialchars($selectedDate ?? 'your selected date') ?>
            </p>

            <div class="grid grid-cols-2 gap-md" style="max-width: 400px; margin: 0 auto;">
                <?php
                $times = ['9:00 AM', '10:00 AM', '11:00 AM', '12:00 PM', '1:00 PM', '2:00 PM', '3:00 PM', '4:00 PM', '5:00 PM', '6:00 PM'];
                foreach ($times as $time):
                    $available = !in_array($time, $bookedTimes ?? []);
                ?>
                    <button
                        class="btn <?= $available ? 'btn-secondary time-slot' : 'btn-ghost' ?>"
                        data-time="<?= $time ?>"
                        <?= !$available ? 'disabled' : '' ?>
                    >
                        <?= $time ?>
                        <?php if (!$available): ?>
                            <span class="text-muted text-sm">(Booked)</span>
                        <?php endif; ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <input type="hidden" id="selected-time" name="time">
        </div>

        <?php elseif ($step === 3): ?>
        <!-- Step 3: Shoot Type -->
        <div class="booking-step-content animate-fade-in-up">
            <h2 class="text-center mb-lg">Type of Photoshoot</h2>

            <div class="grid gap-md" style="max-width: 500px; margin: 0 auto;">
                <?php foreach ($shootTypes as $type): ?>
                    <button class="card shoot-type-card" data-type="<?= $type ?>" style="cursor: pointer;">
                        <h3 class="text-lg font-medium"><?= ucfirst($type) ?></h3>
                        <p class="text-secondary text-sm mt-sm">
                            <?php
                            $descriptions = [
                                'personal' => 'Portraits, headshots, lifestyle photos',
                                'product' => 'Product photography for e-commerce, social media',
                                'group' => 'Couples, families, friend groups',
                                'event' => 'Parties, celebrations, special occasions',
                                'misc' => 'Everything else - let\'s talk about your vision',
                            ];
                            echo $descriptions[$type] ?? '';
                            ?>
                        </p>
                    </button>
                <?php endforeach; ?>
            </div>

            <input type="hidden" id="selected-type" name="shoot_type">
        </div>

        <?php elseif ($step === 4): ?>
        <!-- Step 4: Pre-Booking Questions -->
        <div class="booking-step-content animate-fade-in-up">
            <h2 class="text-center mb-sm">Before We Begin</h2>
            <p class="text-muted text-center text-sm mb-xl">Only 8 are required</p>

            <form id="booking-questions" class="card">
                <div class="form-group">
                    <label class="form-label">What's your name? *</label>
                    <input type="text" name="name" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">What's your email? *</label>
                    <input type="email" name="email" class="form-input" required
                           value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">How long would you like the photoshoot to be? *</label>
                    <select name="duration" class="form-select" required>
                        <option value="">Select duration</option>
                        <option value="2">2 hours - $200</option>
                        <option value="4">4 hours - $375</option>
                        <option value="6">6 hours - $525</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">What type of photoshoot is it? *</label>
                    <select name="type" class="form-select" required>
                        <option value="">Select type</option>
                        <?php foreach ($shootTypes as $type): ?>
                            <option value="<?= $type ?>" <?= ($selectedType ?? '') === $type ? 'selected' : '' ?>>
                                <?= ucfirst($type) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Date *</label>
                    <input type="date" name="date" class="form-input" required
                           value="<?= htmlspecialchars($selectedDate ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Do you have a moodboard or idea for the shoot in mind already?</label>
                    <textarea name="moodboard" class="form-textarea" rows="3"
                              placeholder="Share links, describe your vision..."></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Do you have a location in mind or do you need help finding one? *</label>
                    <select name="location_help" class="form-select" required>
                        <option value="">Select option</option>
                        <option value="have_location">I have a location in mind</option>
                        <option value="need_help">I need help finding a location</option>
                        <option value="open">I'm open to suggestions</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Do you need help finding a studio/paid location?</label>
                    <select name="studio_help" class="form-select">
                        <option value="no">No, I don't need help</option>
                        <option value="yes">Yes, please help me find one</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Do you need help hiring someone for the photoshoot?</label>
                    <select name="hiring_help" class="form-select">
                        <option value="no">No</option>
                        <option value="model">Yes, I need a model</option>
                        <option value="assistant">Yes, I need an assistant</option>
                        <option value="both">Yes, both</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary mt-lg" style="width: 100%;">
                    Continue
                </button>
            </form>
        </div>

        <?php elseif ($step === 5): ?>
        <!-- Step 5: Add-ons -->
        <div class="booking-step-content animate-fade-in-up">
            <h2 class="text-center mb-lg">Add-ons (Optional)</h2>

            <div class="card">
                <div class="addon-item flex justify-between items-center p-md" style="border-bottom: 1px solid var(--bg-hover);">
                    <div>
                        <h4>Model Hiring</h4>
                        <p class="text-muted text-sm">Professional model for your shoot</p>
                    </div>
                    <div class="flex items-center gap-md">
                        <span class="text-violet">$25 - $150</span>
                        <input type="checkbox" name="addon_model" id="addon_model">
                    </div>
                </div>

                <div class="addon-item flex justify-between items-center p-md" style="border-bottom: 1px solid var(--bg-hover);">
                    <div>
                        <h4>Rush Editing</h4>
                        <p class="text-muted text-sm">Get your photos within 48 hours</p>
                    </div>
                    <div class="flex items-center gap-md">
                        <span class="text-violet">+50%</span>
                        <input type="checkbox" name="addon_rush" id="addon_rush">
                    </div>
                </div>

                <div class="addon-item flex justify-between items-center p-md">
                    <div>
                        <h4>Extra Locations</h4>
                        <p class="text-muted text-sm">Additional location changes</p>
                    </div>
                    <div class="flex items-center gap-md">
                        <span class="text-violet">$50/each</span>
                        <input type="number" name="addon_locations" min="0" max="5" value="0"
                               class="form-input" style="width: 60px;">
                    </div>
                </div>
            </div>

            <!-- Price Summary -->
            <div class="card mt-lg">
                <h3 class="mb-md">Summary</h3>
                <div class="flex justify-between mb-sm">
                    <span class="text-secondary">Base price</span>
                    <span id="price-base">$200</span>
                </div>
                <div class="flex justify-between mb-sm" id="addons-row" style="display: none;">
                    <span class="text-secondary">Add-ons</span>
                    <span id="price-addons">$0</span>
                </div>
                <hr style="border-color: var(--bg-hover); margin: 1rem 0;">
                <div class="flex justify-between">
                    <span class="font-medium">Total</span>
                    <span class="text-lg font-medium text-violet" id="price-total">$200</span>
                </div>
                <div class="flex justify-between mt-sm">
                    <span class="text-muted text-sm">Deposit (50%)</span>
                    <span class="text-sm" id="price-deposit">$100</span>
                </div>
            </div>

            <button class="btn btn-primary mt-lg" style="width: 100%;" id="continue-to-contract">
                Continue to Contract
            </button>
        </div>

        <?php elseif ($step === 6): ?>
        <!-- Step 6: Contract -->
        <div class="booking-step-content animate-fade-in-up">
            <h2 class="text-center mb-lg">Review & Sign Contract</h2>

            <div class="card" style="max-height: 400px; overflow-y: auto;">
                <div id="contract-content">
                    <!-- Contract content loaded dynamically -->
                    <h3>Photography Services Agreement</h3>
                    <p class="text-secondary mt-md">
                        This agreement is between TiredOfDoinTM ("Photographer") and
                        <strong><?= htmlspecialchars($bookingData['name'] ?? 'Client') ?></strong> ("Client")
                        for photography services on <strong><?= htmlspecialchars($bookingData['date'] ?? 'TBD') ?></strong>.
                    </p>
                    <!-- More contract terms... -->
                </div>
            </div>

            <div class="card mt-lg">
                <label class="form-label">Signature</label>
                <p class="text-muted text-sm mb-md">Draw or type your signature below</p>

                <canvas id="signature-canvas" width="400" height="150"
                        style="width: 100%; border: 1px solid var(--bg-hover); border-radius: var(--radius-md); background: var(--bg-elevated);"></canvas>

                <div class="flex gap-md mt-md">
                    <button class="btn btn-ghost btn-sm" id="clear-signature">Clear</button>
                    <button class="btn btn-ghost btn-sm" id="type-signature">Type Instead</button>
                </div>

                <div class="form-group mt-lg">
                    <label class="flex items-center gap-sm">
                        <input type="checkbox" name="agree_terms" required>
                        <span class="text-sm">I agree to the terms and conditions above</span>
                    </label>
                </div>

                <button class="btn btn-primary mt-md" style="width: 100%;" id="sign-contract">
                    Sign & Continue to Payment
                </button>
            </div>
        </div>

        <?php elseif ($step === 7): ?>
        <!-- Step 7: Payment -->
        <div class="booking-step-content animate-fade-in-up">
            <h2 class="text-center mb-lg">Payment</h2>

            <div class="card">
                <h3 class="mb-md">Order Summary</h3>
                <div class="flex justify-between mb-sm">
                    <span class="text-secondary"><?= ucfirst($bookingData['type'] ?? 'Photoshoot') ?> - <?= $bookingData['duration'] ?? '2' ?> hours</span>
                    <span>$<?= number_format($bookingData['total'] ?? 200, 2) ?></span>
                </div>
                <hr style="border-color: var(--bg-hover); margin: 1rem 0;">
                <div class="flex justify-between">
                    <span class="font-medium">Deposit Due Today (50%)</span>
                    <span class="text-lg font-medium text-violet">$<?= number_format(($bookingData['total'] ?? 200) / 2, 2) ?></span>
                </div>
                <p class="text-muted text-sm mt-sm">
                    Remaining balance due before your session.
                </p>
            </div>

            <!-- Stripe Payment Element will be mounted here -->
            <div id="payment-element" class="card mt-lg">
                <!-- Stripe loads here -->
            </div>

            <button class="btn btn-primary mt-lg" style="width: 100%;" id="submit-payment">
                Pay $<?= number_format(($bookingData['total'] ?? 200) / 2, 2) ?> Deposit
            </button>

            <p class="text-muted text-sm text-center mt-lg">
                Secure payment powered by Stripe
            </p>
        </div>
        <?php endif; ?>

        <!-- Navigation -->
        <div class="flex justify-between mt-xl">
            <?php if ($step > 1): ?>
                <a href="/booking?step=<?= $step - 1 ?>" class="btn btn-ghost">← Back</a>
            <?php else: ?>
                <div></div>
            <?php endif; ?>

            <?php if ($step < 4): ?>
                <button class="btn btn-primary" id="next-step">Continue →</button>
            <?php endif; ?>
        </div>
    </div>
</section>

<style>
.booking-progress {
    display: flex;
    align-items: center;
}

.booking-step {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: var(--bg-elevated);
    color: var(--text-muted);
    font-size: 0.875rem;
    transition: all var(--transition-base);
}

.booking-step.active {
    background: var(--violet-500);
    color: white;
    box-shadow: var(--glow-violet-medium);
}

.booking-step.completed {
    background: var(--success);
    color: white;
}

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 4px;
}

.calendar-day {
    aspect-ratio: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--radius-md);
    cursor: pointer;
    transition: all var(--transition-fast);
}

.calendar-day:hover:not(.disabled) {
    background: var(--bg-hover);
}

.calendar-day.available {
    background: rgba(74, 222, 128, 0.1);
    color: var(--success);
}

.calendar-day.selected {
    background: var(--violet-500);
    color: white;
}

.calendar-day.disabled {
    color: var(--text-disabled);
    cursor: not-allowed;
}

.time-slot.selected {
    background: var(--violet-500);
    color: white;
}

.shoot-type-card.selected {
    border-color: var(--violet-500);
    box-shadow: var(--glow-violet-medium);
}
</style>

<script>
// Booking step navigation
document.getElementById('next-step')?.addEventListener('click', function() {
    const currentStep = <?= $step ?>;
    let canProceed = false;

    // Validate current step
    if (currentStep === 1) {
        canProceed = document.getElementById('selected-date')?.value;
    } else if (currentStep === 2) {
        canProceed = document.getElementById('selected-time')?.value;
    } else if (currentStep === 3) {
        canProceed = document.getElementById('selected-type')?.value;
    }

    if (canProceed) {
        // In real app, save to session/localStorage and redirect
        window.location.href = `/booking?step=${currentStep + 1}`;
    } else {
        TODT.utils.notify('Please make a selection', 'error');
    }
});

// Time slot selection
document.querySelectorAll('.time-slot').forEach(slot => {
    slot.addEventListener('click', function() {
        document.querySelectorAll('.time-slot').forEach(s => s.classList.remove('selected'));
        this.classList.add('selected');
        document.getElementById('selected-time').value = this.dataset.time;
    });
});

// Shoot type selection
document.querySelectorAll('.shoot-type-card').forEach(card => {
    card.addEventListener('click', function() {
        document.querySelectorAll('.shoot-type-card').forEach(c => c.classList.remove('selected'));
        this.classList.add('selected');
        document.getElementById('selected-type').value = this.dataset.type;
    });
});
</script>
