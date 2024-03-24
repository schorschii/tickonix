function toggleCheckboxesInContainer(container, checked) {
	let items = container.children;
	for(var i = 0; i < items.length; i++) {
		if(items[i].style.display == 'none') continue;
		let inputs = items[i].getElementsByTagName('input');
		for(var n = 0; n < inputs.length; n++) {
			if(inputs[n].type == 'checkbox' && !inputs[n].disabled) {
				inputs[n].checked = checked;
			}
		}
	}
}
