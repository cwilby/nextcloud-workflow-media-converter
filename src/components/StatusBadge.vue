<template>
	<div :class="`wmc-status-badge ${shouldPulse ? 'pulse' : ''}`" :style="{ backgroundColor: colorMap[status] }">
		{{ status }}
	</div>
</template>

<script>
export default {
	props: {
		status: {
			required: true,
			type: String,
		},
	},

	data: () => ({
		colorMap: {
			queued: '#17a2b8',
			seeking: '#007bff',
			converting: '#007bff',
			'has-failures': '#ffc107',
			failed: '#ff4402',
			finished: '#28a745',
		},
	}),

	computed: {
		shouldPulse() {
			return ['queued', 'seeking', 'converting'].includes(this.status)
		},
	},
}
</script>

<style lang="scss">
.wmc-status-badge {
	display: flex;
	width: 8em;
	align-items: center;
	justify-content: center;
	padding: .25em;
	border-radius: .25em;
	text-transform: capitalize;
	color: white;
}

.pulse {
	animation-name: color;
	animation-duration: 2s;
	animation-iteration-count: infinite;
}

@keyframes color {
	0% {
		opacity: .75
	}
	50% {
		opacity: 1
	}
	100% {
		opacity: .75
	}
}
</style>
