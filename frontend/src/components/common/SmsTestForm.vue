<script setup>
import { computed, ref } from 'vue'

const phoneNumber = ref('')
const message = ref('')
const loading = ref(false)
const successMessage = ref('')
const errorMessage = ref('')

const apiBaseUrl = computed(() => String(import.meta.env.VITE_APP_DOMAIN_API_URL || '').replace(/\/$/, ''))
const smsEndpoint = computed(() => `${apiBaseUrl.value}/api/sms/test`)

const resolveErrorMessage = payload => {
  if (typeof payload?.message === 'string' && payload.message.trim())
    return payload.message

  if (payload?.errors && typeof payload.errors === 'object')
    return Object.values(payload.errors).flat().join(' ')

  return 'No se pudo enviar el SMS de prueba.'
}

const resetFeedback = () => {
  successMessage.value = ''
  errorMessage.value = ''
}

const handleSubmit = async () => {
  resetFeedback()

  if (!apiBaseUrl.value) {
    errorMessage.value = 'Falta configurar VITE_APP_DOMAIN_API_URL.'

    return
  }

  if (!phoneNumber.value.trim() || !message.value.trim()) {
    errorMessage.value = 'Debes completar el número y el mensaje.'

    return
  }

  loading.value = true

  try {
    const response = await fetch(smsEndpoint.value, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      body: JSON.stringify({
        phone_number: phoneNumber.value.trim(),
        message: message.value.trim(),
      }),
    })

    const data = await response.json().catch(() => ({}))

    if (!response.ok)
      throw new Error(resolveErrorMessage(data))

    successMessage.value = data?.message || 'SMS enviado correctamente.'
    message.value = ''
  } catch (error) {
    errorMessage.value = error instanceof Error ? error.message : 'No se pudo enviar el SMS de prueba.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <section class="sms-test-card">
    <div class="sms-test-header">
      <p class="sms-test-kicker">
        Twilio Test
      </p>
      <h2 class="sms-test-title">
        Send SMS
      </h2>
      <p class="sms-test-description">
        Send Message Test
      </p>
    </div>

    <form class="sms-test-form" @submit.prevent="handleSubmit">
      <div class="sms-test-field">
        <label class="sms-test-label" for="twilio-test-phone-number">
          Phone Number
        </label>
        <input
          id="twilio-test-phone-number"
          v-model="phoneNumber"
          type="text"
          inputmode="tel"
          placeholder="+573001234567"
          class="sms-test-input"
        >
      </div>

      <div class="sms-test-field">
        <label class="sms-test-label" for="twilio-test-message">
          Message
        </label>
        <textarea
          id="twilio-test-message"
          v-model="message"
          rows="5"
          maxlength="1600"
          placeholder="Write your test message"
          class="sms-test-input sms-test-textarea"
        />
      </div>

      <div class="sms-test-actions">
        <button
          type="submit"
          class="sms-test-button"
          :disabled="loading"
        >
          {{ loading ? 'Sending...' : 'Send SMS' }}
        </button>
      </div>

      <p v-if="successMessage" class="sms-test-feedback sms-test-feedback-success">
        {{ successMessage }}
      </p>

      <p v-if="errorMessage" class="sms-test-feedback sms-test-feedback-error">
        {{ errorMessage }}
      </p>
    </form>
  </section>
</template>

<style scoped>
.sms-test-card {
  border: 1px solid #e5e7eb;
  border-radius: 24px;
  background: #fff;
  padding: 24px;
  box-shadow: 0 18px 40px rgb(15 23 42 / 8%);
}

.sms-test-header {
  margin-bottom: 20px;
}

.sms-test-kicker {
  margin: 0;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #64748b;
}

.sms-test-title {
  margin: 8px 0 0;
  font-size: 28px;
  font-weight: 700;
  line-height: 1.1;
  color: #0f172a;
}

.sms-test-description {
  max-width: 52ch;
  margin: 10px 0 0;
  font-size: 14px;
  line-height: 1.6;
  color: #64748b;
}

.sms-test-form {
  display: grid;
  gap: 18px;
}

.sms-test-field {
  display: grid;
  gap: 8px;
}

.sms-test-label {
  font-size: 13px;
  font-weight: 600;
  color: #334155;
}

.sms-test-input {
  width: 100%;
  border: 1px solid #cbd5e1;
  border-radius: 14px;
  background: #f8fafc;
  padding: 12px 14px;
  font-size: 15px;
  color: #0f172a;
  outline: none;
  transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
}

.sms-test-input:focus {
  border-color: #0f172a;
  background: #fff;
  box-shadow: 0 0 0 4px rgb(15 23 42 / 8%);
}

.sms-test-textarea {
  min-height: 140px;
  resize: vertical;
}

.sms-test-actions {
  display: flex;
  justify-content: flex-start;
}

.sms-test-button {
  min-width: 180px;
  border: 0;
  border-radius: 999px;
  background: #0f172a;
  padding: 12px 20px;
  font-size: 15px;
  font-weight: 600;
  color: #fff;
  cursor: pointer;
  transition: background-color 0.2s ease, opacity 0.2s ease;
}

.sms-test-button:hover {
  background: #1e293b;
}

.sms-test-button:disabled {
  cursor: not-allowed;
  opacity: 0.65;
}

.sms-test-feedback {
  margin: 0;
  border-radius: 14px;
  padding: 12px 14px;
  font-size: 13px;
  line-height: 1.5;
}

.sms-test-feedback-success {
  background: #ecfdf5;
  color: #047857;
}

.sms-test-feedback-error {
  background: #fff1f2;
  color: #be123c;
}

@media (max-width: 640px) {
  .sms-test-card {
    padding: 18px;
    border-radius: 20px;
  }

  .sms-test-title {
    font-size: 24px;
  }

  .sms-test-actions {
    justify-content: stretch;
  }

  .sms-test-button {
    width: 100%;
  }
}
</style>