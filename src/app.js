/* global wpLazyLoaderEmbeds */
const targets = [];
const supportsIntersectionObserver = 'IntersectionObserver' in window;
let io = null;

/**
 * Load a script async into the document.
 *
 * @param {String} src 
 */
const loadScript = (src) => {
	const script = document.createElement('script');
	script.src = src;
	script.async = true;
	document.body.appendChild(script);
};

/**
 * Callback for Intersection Observer.
 * 
 * @param {Array} entries 
 * @param {Object} observer 
 */
const observerCallback = (entries, observer) => {
	entries.forEach((entry) => {
		if (0 < entry.intersectionRatio) {
			const index = targets.findIndex((item) => item.target === entry.target);
			
			if (index !== -1) {
				const { callback } = targets[index];

				// Stop observing.
				observer.unobserve(entry.target);

				// Call the callback loading the script.
				callback();

				// Remove target.
				targets.splice(index, 1);
			}
		}

		// Stop observing if we're out of targets.
		if (0 === targets.length) {
			observer.disconnect();
		}
	});
};

/**
 * Initialize IntersectionObserver and configure targets and their respective scripts.
 */
const init = () => {
	console.log(wpLazyLoaderEmbeds);
    wpLazyLoaderEmbeds.forEach(({selector, script}) => {
		const target = document.querySelector(selector);

		if (! target) {
			return;
		}

		const callback = () => {
			loadScript(script);
		};

		if (io) {
			targets.push({
				target,
				callback,
			});

			io.observe(target);
		} else {
			callback();
		}
	});
};

if (supportsIntersectionObserver) {
	io = new IntersectionObserver(observerCallback);
	init();
} else {
	wpLazyLoaderEmbeds.forEach(({script}) => {
        loadScript(script);
    });
}