function toggleOnOff() {
    let checkbox = document.getElementById("enabled");
    if (checkbox.checked == false && confirm("Are you sure you want to turn backups off?")) {
        return true;
    } else {
        checkbox.checked = true;
    }
}

function toggle(select) {
    // hide all
    for (const elem of document.getElementsByClassName('inside padded dark-bg')) {
        elem.style.display = 'none';
    };

    // show selection
    let selection = select.options[select.selectedIndex].value;
    if (selection == 'sftp_scp') {
        var cont = document.getElementById('sftp_scp');
    } else if (selection == 'gcs') {
        var cont = document.getElementById('gcs');
    }
    cont.style.display = 'block';
}