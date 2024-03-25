import { onDomLoad } from '../../../front/modules/lib/load';
import { htmlToFragment } from '../../../front/scripts/shared/html';

type ParsedRollerData = {
	cumulativeWeights: number[];
	items: string[];
};

onDomLoad(() => {
	const rollerRawData = document.querySelectorAll('script[type="text/random-selection-ext-data"]');
	rollerRawData.forEach((element) => {
		const { items, cumulativeWeights }: ParsedRollerData = JSON.parse(element.innerHTML);
		const maxCumulativeWeight = cumulativeWeights[cumulativeWeights.length - 1];
		const roll = Math.random() * maxCumulativeWeight;

		let rolledValue = '';
		for (let i = 0; i < cumulativeWeights.length; i++) {
			if (roll < cumulativeWeights[i]) {
				rolledValue = items[i];
				break;
			}
		}
		element.replaceWith(htmlToFragment(rolledValue));
	});
});
