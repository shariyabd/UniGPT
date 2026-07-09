<script setup>
import { computed } from 'vue';
import { Bar, Line } from 'vue-chartjs';
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    LineElement,
    PointElement,
    CategoryScale,
    LinearScale,
    Filler,
} from 'chart.js';

ChartJS.register(
    Title,
    Tooltip,
    Legend,
    BarElement,
    LineElement,
    PointElement,
    CategoryScale,
    LinearScale,
    Filler,
);

const props = defineProps({
    type: { type: String, default: 'bar' }, // 'bar' | 'line'
    series: { type: Array, default: () => [] }, // [{ label, value }]
    label: { type: String, default: '' },
    color: { type: String, default: '#6366f1' },
    // Fixed 0–100 axis for percentage/GPA-style metrics; omit to auto-scale.
    max: { type: Number, default: null },
});

const chartComponent = computed(() => (props.type === 'line' ? Line : Bar));

const chartData = computed(() => ({
    labels: props.series.map((point) => point.label),
    datasets: [
        {
            label: props.label,
            data: props.series.map((point) => point.value),
            backgroundColor: props.type === 'line' ? `${props.color}33` : props.color,
            borderColor: props.color,
            borderWidth: 2,
            borderRadius: props.type === 'bar' ? 6 : 0,
            tension: 0.35,
            fill: props.type === 'line',
            pointBackgroundColor: props.color,
            pointRadius: 3,
            maxBarThickness: 48,
        },
    ],
}));

// Grey that reads acceptably in both light and dark themes.
const gridColor = 'rgba(148, 163, 184, 0.2)';
const tickColor = 'rgba(148, 163, 184, 0.9)';

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: { intersect: false, mode: 'index' },
    },
    scales: {
        x: {
            grid: { display: false },
            ticks: { color: tickColor, font: { size: 11 } },
        },
        y: {
            beginAtZero: true,
            ...(props.max !== null ? { max: props.max } : {}),
            grid: { color: gridColor },
            ticks: { color: tickColor, font: { size: 11 } },
        },
    },
}));
</script>

<template>
    <div class="h-64">
        <component :is="chartComponent" :data="chartData" :options="chartOptions" />
    </div>
</template>
