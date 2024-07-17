<template>
	<div class="center-loading">
		<NcLoadingIcon v-if="loading"
			:size="34"
			appearance="dark"
			name="Loading on light background" />
	</div>
</template>
<script>
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
export default {
	name: 'CustomLoadingIcon',
	components: {
		NcLoadingIcon,
	},
	props: {
		loading: {
			type: Boolean,
			default: false,
		},
	},

}
</script>
<style scoped>
.center-loading {
	/* width: 50px;*/
	position: absolute;
	top: 50%;
	left: 45%;
}
</style>
