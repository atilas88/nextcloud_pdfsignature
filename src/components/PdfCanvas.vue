<template>
	<div>
		<canvas ref="canvas"
			@mousedown="mouseDown"
			@mouseup="mouseUp"
			@mouseout="mouseUp"
			@mousemove="mouseMove"
			@dblclick="dbClick" />
	</div>
</template>
<script>
export default {
	name: 'PdfCanvas',
	props: {
		imageData: {
			type: String,
			required: true,
			validator(value) {
				return value.startsWith('data:image/jpeg;base64,')
			},
		},
	},
	data() {
		return {
			context: null,
			canva: null,
			backgroundImage: null,
			image_x: 0,
			image_y: 0,
			imageWidth: 0,
			imageHeight: 0,
			shape: {
				x: 10,
				y: 10,
				height: 40,
				width: 100,
				color: 'gray',
			},
			is_dragging: false,
			start_x: 0,
			start_y: 0,
			offset_x: 0,
			offset_y: 0,
		}
	},
	computed: {
		computeCanvawidth() {
			return this.canva.width
		},
		computeCanvaHeight() {
			return this.canva.height
		},
		computeCanvasAspectRatio() {
			return this.canva.width / this.canva.height
		},

	},
	mounted() {
		this.canva = this.$refs.canvas
		this.context = this.canva.getContext('2d')
		this.canva.style.background = 'white'

		const mediaQuery = window.matchMedia('( width < 500px )')
		if (mediaQuery.matches) {
			this.canva.width = 320
			this.canva.height = 650
		} else {
			this.canva.width = 500
			this.canva.height = 700
		}
		this.backgroundImage = new Image()
		this.backgroundImage.src = this.imageData

		// Get original Vue obj
		const vm = this
		this.backgroundImage.onload = function() {

			const imgAspectRatio = this.width / this.height
			if (vm.computeCanvasAspectRatio > imgAspectRatio) {
				vm.imageWidth = vm.computeCanvawidth
				vm.imageHeight = vm.imageWidth / imgAspectRatio
			} else {
				// Canvas is taller or has the same aspect ratio, scale image height to cover the canvas height
				vm.imageHeight = vm.computeCanvaHeight
				vm.imageWidth = vm.imageHeight * imgAspectRatio
			}
			// Calculate the position to center the image on the canvas
			vm.image_x = (vm.computeCanvawidth - vm.imageWidth) / 2
			vm.image_y = (vm.computeCanvaHeight - vm.imageHeight) / 2
			vm.drawShape()
		}
	},
	methods: {
		drawShape() {
			this.context.clearRect(0, 0, this.computeCanvawidth, this.computeCanvaHeight)
			this.context.drawImage(this.backgroundImage, this.image_x, this.image_y, this.imageWidth, this.imageHeight)
			this.context.fillStyle = this.shape.color
			this.context.fillRect(this.shape.x, this.shape.y, this.shape.width, this.shape.height)
		},
		getOffset() {
			const canvasOffset = this.canva.getBoundingClientRect()
			this.offset_x = canvasOffset.left
			this.offset_y = canvasOffset.top
		},
		isMouseInShape(x, y) {
			const shapeLeft = this.shape.x
			const shapeRight = this.shape.x + this.shape.width
			const shapeTop = this.shape.y
			const shapeBottom = this.shape.y + this.shape.height

			if (x > shapeLeft && x < shapeRight && y > shapeTop && y < shapeBottom) {
				return true
			} else {
				return false
			}
		},
		mouseDown(event) {
			event.preventDefault()
			this.getOffset()
			this.start_x = parseInt(event.clientX - this.offset_x)
			this.start_y = parseInt(event.clientY - this.offset_y)

			if (this.isMouseInShape(this.start_x, this.start_y)) {
				this.is_dragging = true
			}
		},
		mouseUp(event) {
			if (this.is_dragging) {
				event.preventDefault()
				this.is_dragging = false
			}
		},
		mouseMove(event) {
			if (this.is_dragging) {
				event.preventDefault()
				this.getOffset()
				const mouseX = parseInt(event.clientX - this.offset_x)
				const mouseY = parseInt(event.clientY - this.offset_y)

				const dx = mouseX - this.start_x
				const dy = mouseY - this.start_y

				this.shape.x += dx
				this.shape.y += dy

				this.drawShape()
				this.start_x = mouseX
				this.start_y = mouseY
			}
		},
		coordPercentage(value, dimension) {
			return parseInt((value * 100) / dimension)
		},
		dbClick(event) {
			event.preventDefault()
			const realY = this.canva.height - this.shape.y
			const xPercent = this.coordPercentage(this.shape.x, this.canva.width)
			const yPercent = this.coordPercentage(realY, this.canva.height)
			this.$emit('set-logo-place', xPercent, yPercent)
		},
	},
}
</script>
<style>
canvas{
	margin: 45px;
}
@media (width < 510px ){
	canvas{
		margin: 25px;
	}
}
</style>
