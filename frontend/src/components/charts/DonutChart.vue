<script setup lang="ts">
import { computed, watch } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import { useTheme } from '@/composables/useTheme'

interface Props {
  series: number[]
  labels: string[]
  height?: number
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  height: 300,
  loading: false,
})

const { theme } = useTheme()

const textColor = computed(() => theme.value === 'dark' ? '#94A3B8' : '#64748B')

const chartOptions = computed(() => ({
  chart: {
    type: 'donut' as const,
    background: 'transparent',
    fontFamily: 'Inter, system-ui, sans-serif',
  },
  colors: ['#00D4AA', '#6C63FF', '#FF4D6D', '#F59E0B', '#22C55E'],
  labels: props.labels,
  legend: {
    position: 'bottom' as const,
    labels: { colors: textColor.value },
    fontSize: '13px',
  },
  dataLabels: {
    style: { fontSize: '12px' },
  },
  tooltip: {
    theme: theme.value,
    style: { fontSize: '12px' },
  },
  plotOptions: {
    pie: {
      donut: {
        size: '65%',
      },
    },
  },
  stroke: {
    show: false,
  },
}))

// Re-render chart when theme changes
watch(theme, () => {
  // chartOptions is reactive via computed, ApexCharts will pick up the change
})
</script>

<template>
  <div class="donut-chart">
    <!-- Loading skeleton -->
    <div v-if="loading" class="donut-chart__skeleton">
      <div class="skeleton skeleton--circle" :style="{ height: `${height}px` }" />
    </div>

    <!-- Chart -->
    <VueApexCharts
      v-else
      type="donut"
      :height="height"
      :options="chartOptions"
      :series="series"
      width="100%"
    />
  </div>
</template>

<style lang="scss" scoped>
.donut-chart {
  width: 100%;
}

@keyframes shimmer {
  0% { background-position: -200% 0; }
  100% { background-position: 200% 0; }
}

.skeleton {
  border-radius: var(--radius-md);
  background: linear-gradient(
    90deg,
    var(--color-surface-2) 25%,
    var(--color-border) 50%,
    var(--color-surface-2) 75%
  );
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;

  &--circle {
    width: 100%;
  }
}
</style>
