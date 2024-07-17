<template>
	<div>
		<NcModal
			v-if="modal"
			title=""
			@close="closeModal">
			<div class="modal__content">
				<TreeItem :model="treeData" />
			</div>
		</NcModal>
	</div>
</template>
<script>
import NcModal from '@nextcloud/vue/dist/Components/NcModal.js'
import TreeItem from './TreeItem.vue'

export default {
	name: 'InfoModal',
	components: {
		NcModal,
		TreeItem,
	},
	data() {
		return {
			modal: false,
			treeData: '',
		}
	},
	methods: {
		showModal(data) {
			this.modal = true
			this.treeData = data
		},
		closeModal() {
			this.modal = false
		},
	},
}
</script>
<style scoped>
.modal__content {
	margin: 50px;
	/*text-align: center;*/
}

</style>
