$(() => {
	$('script[type="text/rnd-sel-init"]').each((idx, item) => {
		let options = [];
		try{
			options = JSON.parse(item.text).o;
		} catch (error) {
			console.error('Cannot parse options list for random selection: ' + error);
			return;
		}

		// this is in WIP state, weights needs to be implemented here
		const ol = options.length;
		if (ol > 0) {
			const r = Math.random();
			console.log(r);
			for (let i = 0; i < ol; i++) {
				if ((i + 1) / ol > r) {
					console.log('randomized option' + ' ' + (i + 1) + '\n' + options[i]);
					// add print logic here
					item.insertAdjacentHTML('afterend', options[i]);
					return;
				}
			}
		}
	})
})
