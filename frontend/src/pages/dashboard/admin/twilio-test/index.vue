<script setup>
import SmsTestForm from '@/components/common/SmsTestForm.vue'
</script>

<template>
  <section class="twilio-test-page">
    <header class="twilio-test-page__hero">
      <div>
        <p class="twilio-test-page__eyebrow">
          Services
        </p>
        <h1 class="twilio-test-page__title">
          Twilio Test
        </h1>
        <p class="twilio-test-page__copy">
          Message sending test using Twilio API.
        </p>
      </div>
    </header>

    <SmsTestForm />
  </section>
</template>

<style scoped>
.twilio-test-page {
  display: grid;
  gap: 24px;
}

.twilio-test-page__hero {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 16px;
}

.twilio-test-page__eyebrow {
  margin: 0;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #64748b;
}

.twilio-test-page__title {
  margin: 8px 0 0;
  font-size: 32px;
  font-weight: 700;
  line-height: 1.05;
  color: #0f172a;
}

.twilio-test-page__copy {
  max-width: 60ch;
  margin: 10px 0 0;
  font-size: 14px;
  line-height: 1.6;
  color: #64748b;
}

@media (max-width: 640px) {
  .twilio-test-page {
    gap: 18px;
  }

  .twilio-test-page__title {
    font-size: 26px;
  }
}
</style>

<route lang="yaml">
  meta:
    action: view
    subject: dashboard
</route>