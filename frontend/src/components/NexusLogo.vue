<script setup lang="ts">
interface Props {
  width?: number
  iconOnly?: boolean
}

withDefaults(defineProps<Props>(), {
  width: 152,
  iconOnly: false,
})
</script>

<template>
  <svg
    :width="iconOnly ? 36 : width"
    :height="40"
    :viewBox="iconOnly ? '0 0 36 40' : '0 0 152 40'"
    xmlns="http://www.w3.org/2000/svg"
    role="img"
    aria-label="NexusTrade"
    fill="none"
  >
    <defs>
      <linearGradient id="nt-logo-g" x1="0" y1="0" x2="36" y2="40" gradientUnits="userSpaceOnUse">
        <stop offset="0%" stop-color="#00D4AA" />
        <stop offset="100%" stop-color="#00907A" />
      </linearGradient>
    </defs>

    <!-- Icon mark: rounded square -->
    <rect x="0" y="2" width="36" height="36" rx="8" fill="url(#nt-logo-g)" />

    <!-- N lettermark -->
    <rect x="7" y="9" width="5" height="22" fill="white" />
    <polygon points="12,9 17,9 25,31 20,31" fill="white" />
    <rect x="24" y="9" width="5" height="22" fill="white" />

    <!-- Chart tick mark — subtle accent -->
    <circle cx="29" cy="8" r="2.5" fill="white" opacity="0.5" />

    <!-- Wordmark (hidden in icon-only mode) -->
    <template v-if="!iconOnly">
      <text
        x="46"
        y="23"
        font-family="Inter, system-ui, -apple-system, sans-serif"
        font-size="16"
        font-weight="800"
        fill="currentColor"
        letter-spacing="-0.4"
      >NEXUS</text>
      <text
        x="47"
        y="35"
        font-family="Inter, system-ui, -apple-system, sans-serif"
        font-size="9"
        font-weight="600"
        fill="#00D4AA"
        letter-spacing="4"
      >TRADE</text>
    </template>
  </svg>
</template>
