<template>
	<NcAppContent>
		<div id="aktenschrank">
			<h1>Hello world!</h1>
		</div>
	</NcAppContent>
</template>

<script>
import NcAppContent from '@nextcloud/vue/dist/Components/NcAppContent.js'

export default {
	name: 'App',
	components: {
		NcAppContent,
	},
}
</script>

<style scoped lang="scss">
#aktenschrank {
	display: flex;
	justify-content: center;
	margin: 16px;
}
</style>
