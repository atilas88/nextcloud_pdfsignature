<template>
	<div class="fup-width" :class="{color_file_selected:isFileSelected}">
		<label>
			{{ theFileName }}
			<input
				class="hide_file"
				type="file"
				v-bind="$attrs"
				@change="handleFileChange($event)">
		</label>
		<div v-if="errors.length > 0">
			<div
				v-for="(error, index) in errors"
				:key="index"
				class="file-upload__error">
				<span>{{ error }}</span>
			</div>
		</div>
	</div>
</template>
<script>
const fileTypes = [
	'image/apng',
	'image/bmp',
	'image/gif',
	'image/jpeg',
	'image/pjpeg',
	'image/png',
	'image/svg+xml',
	'image/tiff',
	'image/webp',
	'image/x-icon',
	'application/p12',
	'application/x-pkcs12',
	'application/pkcs12',
	'application/pfx',
]
export default {
	name: 'FileUpload',
	props: {
		only: {
			type: String,
			default: 'application/*',
		},
		labelPlaceholder: {
			type: String,
			default: 'No file choosen',
		},
	},
	data() {
		return {
			errors: [],
			isLoading: false,
			uploadReady: true,
			file: {
				name: '',
				type: '',
				url: '',
				fullName: '',
				isUploaded: false,
			},

		}
	},
	computed: {
		isFileSelected() {
			return this.file.name !== ''
		},
		theFileName() {
			if (this.isFileSelected) {
				return this.file.fullName
			}
			return this.labelPlaceholder
		},
	},
	methods: {
		handleFileChange(e) {
			this.errors = []
			// Check if file is selected
			if (e.target.files && e.target.files[0]) {
				// Check if file is valid
				if (this.isFileValid(e.target.files[0])) {
				// Get uploaded file
					const file = e.target.files[0]
					// Get file name
					const fileName = file.name.split('.').shift()

					const reader = new FileReader()
					reader.readAsDataURL(file)
					reader.addEventListener('load', () => {
						this.file = {
							name: fileName,
							type: file.type,
							url: reader.result,
							fullName: file.name,
							isUploaded: true,
						}
						this.$emit('file-uploaded', this.file)
					})

				}
			} else {
				console.debug('Invalid file')
			}
		},
		isFileTypeValid(file) {
			if (!fileTypes.includes(file.type)) {
				this.errors.push(t('pdfsignature', 'File type should be {type}', { type: this.only }))
			}
		},
		isFileValid(file) {
			this.isFileTypeValid(file)
			if (this.errors.length === 0) {
				return true
			} else {
				return false
			}
		},
	},
}
</script>
<style scoped>
.file-upload__error {
	color: #f00;
}

.fup-width {
	box-sizing: border-box;
	width:100%;
	border: 2px solid var(--color-border-maxcontrast);
	height: 36px;
	border-radius: var(--border-radius-large);
	font-size: var(--default-font-size);
	background-color: var(--color-main-background);
	cursor: pointer;
	color: #5c5b5b;
	text-align: left;
	padding: 0 12px;
}

.fup-width:hover {
	border-color: var(--color-primary-element);
}

.hide_file {
	visibility:hidden;
}

.color_file_selected {
	color:var(--color-main-text);
}
</style>
