<script setup lang="ts">
import { computed, watch } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import { useTheme } from '@/composables/useTheme'

interface Props {
  series: { name: string; data: number[] }[]
  categories: string[]
  height?: number
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  height: 300,
  loading: false,
})

const { theme } = useTheme()

const textColor = computed(() => theme.value === 'dark' ? '#94A3B8' : '#64748B')
const gridColor = computed(() => theme.value === 'dark' ? '#2A3347' : '#E2E8F0')

const chartOptions = computed(() => ({
  chart: {
    type: 'line' as const,
    background: 'transparent',
    toolbar: { show: false },
    zoom: { enabled: false },
    fontFamily: 'Inter, system-ui, sans-serif',
  },
  colors: ['#00D4AA'],
  stroke: {
    curve: 'smooth' as const,
    width: 2,
  },
  xaxis: {
    categories: props.categories,
    labels: {
      style: { colors: textColor.value, fontSize: '12px' },
    },
    axisBorder: { show: false },
    axisTicks: { show: false },
  },
  yaxis: {
    labels: {
      style: { colors: textColor.value, fontSize: '12px' },
    },
  },
  grid: {
    borderColor: gridColor.value,
    strokeDashArray: 4,
    xaxis: { lines: { show: false } },
  },
  tooltip: {
    theme: theme.value,
    style: { fontSize: '12px' },
  },
  legend: {
    labels: { colors: textColor.value },
  },
  dataLabels: { enabled: false },
}))

// Re-render chart when theme changes
watch(theme, () => {
  // chartOptions is reactive via computed, ApexCharts will pick up the change
})
</script>

<template>
  <div class="line-chart">
    <!-- Loading skeleton -->
    <div v-if="loading" class="line-chart__skeleton">
      <div class="skeleton skeleton--chart" :style="{ height: `${height}px` }" />
    </div>

    <!-- Chart -->
    <VueApexCharts
      v-else
      type="line"
      :height="height"
      :options="chartOptions"
      :series="series"
      width="100%"
    />
  </div>
</template>

<style lang="scss" scoped>
.line-chart {
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

  &--chart {
    width: 100%;
  }
}
</style>
