<template>
	<div>
		<div
			:class="{ bold: isFolder }"
			@click="toggle">
			{{ model.name }}
			<!-- <span v-if="isFolder">[{{ isOpen ? '-' : '+' }}]</span> -->
		</div>
		<ul v-show="isOpen" v-if="isFolder">
			<TreeItem
				v-for="model in model.children"
				:key="model.name"
				:model="model" />
		</ul>
	</div>
</template>
<script>

export default {
	name: 'TreeItem', // necessary for self-reference
	props: {
		model: Object,
	},
	data() {
		return {
			isOpen: false,
		}
	},
	computed: {
		isFolder() {
			return this.model.children && this.model.children.length
		},
	},
	methods: {
		toggle() {
			if (this.isFolder) {
				this.isOpen = !this.isOpen
			}
		},
	},
}
</script>
<style>
.bold {
	font-weight: bold;
	cursor: pointer;
}
</style>
