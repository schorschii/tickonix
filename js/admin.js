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

var html5QrcodeScanner, lastResult;
function onScanSuccess(decodedText, decodedResult) {
	if(decodedText !== lastResult) {
		lastResult = decodedText;
		html5QrcodeScanner.pause(true);
		checkCode(sltEvent.value, decodedText, rdoCheckout.checked ? 'checkout' : 'checkin', html5QrcodeScanner);
	}
}
function startScanner() {
	qrContainer.classList.add('active');
	html5QrcodeScanner = new Html5QrcodeScanner('qrScanner', { fps: 10, qrbox: 250 });
	html5QrcodeScanner.render(onScanSuccess);
}
function stopScanner() {
	qrContainer.classList.remove('active');
	checkResult.classList.remove('active');
	html5QrcodeScanner.clear();
	lastResult = null;
}
function checkCode(event, code, mode, scanner=null) {
	if(!event || !code) return;

	txtCheckCode.value = '';
	checkResult.innerText = code;
	checkResult.classList.add('active');
	checkResult.classList.remove('green');
	checkResult.classList.remove('yellow');
	checkResult.classList.remove('red');

	const xhr = new XMLHttpRequest();
	xhr.open('POST', 'check.php?event='+encodeURIComponent(event));
	xhr.setRequestHeader('Content-Type', 'application/json');
	xhr.onreadystatechange = () => {
		if(xhr.readyState == XMLHttpRequest.DONE) {
			if(xhr.status == 200) {
				let response = JSON.parse(xhr.responseText);
				// show message
				updateCheckResult(response['info'], response['infoClass'], scanner);
				// play sound
				var audio = new Audio(response['sound']);
				audio.play();
				// reload table
				let tblTicketsBody = tblTickets.querySelectorAll('tbody')[0];
				tblTicketsBody.innerHTML = response['rows'];
				spnCount.innerText = response['count'];
				spnCheckedIn.innerText = response['checked_in'];
				spnCheckedOut.innerText = response['checked_out'];
				spnRevoked.innerText = response['revoked'];
			} else {
				updateCheckResult(xhr.status, 'red', scanner);
			}
		}
	};
	xhr.send(JSON.stringify({'check':code, 'mode':mode}));
}
function updateCheckResult(info, infoClass, scanner=null) {
	let delay = 2500;
	if(infoClass == 'green') delay = 1800;
	checkResult.classList.add(infoClass);
	checkResult.innerText = info;
	setTimeout(function(){
		let animation = checkResult.animate(
			[ {opacity:1}, {opacity:0} ],
			{ duration: 250, iterations: 1, easing:'ease' }
		);
		animation.onfinish = (event) => {
			lastResult = null;
			if(scanner) scanner.resume();
			checkResult.classList.remove('active');
		};
	}, delay);
}
document.addEventListener('DOMContentLoaded', function() {
	if(document.getElementById('txtCheckCode')) {
		txtCheckCode.addEventListener("keypress", function(event) {
			if(event.key === 'Enter') {
				event.preventDefault();
				btnCheckCode.click();
			}
		});
	}
}, false);
