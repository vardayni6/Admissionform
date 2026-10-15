/* =====================================================
   SMALL JAVASCRIPT FUNCTIONS USED BY THE PAGES
   ===================================================== */


/* ---------- Show / Hide password ----------
   used in: signin.php, registration.php, admin-signin.php */

function togglePassword(inputId, button) {

    var input = document.getElementById(inputId);

    if (input.type === "password") {
        input.type = "text";
        button.textContent = "Hide";
    } else {
        input.type = "password";
        button.textContent = "Show";
    }
}


/* ---------- Percentage calculator ----------
   used in: academic.php
   percentage = obtained / total * 100  */

function setupPercentage(totalId, obtainedId, percentageId) {

    var total      = document.getElementById(totalId);
    var obtained   = document.getElementById(obtainedId);
    var percentage = document.getElementById(percentageId);

    function calculate() {

        var t = parseFloat(total.value);
        var o = parseFloat(obtained.value);

        if (t > 0 && o >= 0 && o <= t) {
            percentage.value = ((o / t) * 100).toFixed(2);
        } else {
            percentage.value = "";
        }
    }

    total.addEventListener("input", calculate);
    obtained.addEventListener("input", calculate);

    calculate();
}


/* ---------- Same as permanent address ----------
   used in: personal.php */

function setupSameAddress() {

    var checkbox = document.getElementById("same_as_permanent");

    var names = [
        "address_line1", "address_line2", "city_village", "taluka",
        "district", "state", "country", "pin_code"
    ];

    /* copy permanent values into temporary boxes */
    function copyAddress() {

        for (var i = 0; i < names.length; i++) {
            var from = document.getElementById("permanent_" + names[i]);
            var to   = document.getElementById("temporary_" + names[i]);

            if (checkbox.checked) {
                to.value    = from.value;
                to.readOnly = true;
            } else {
                to.readOnly = false;
            }
        }
    }

    checkbox.addEventListener("change", copyAddress);

    /* if permanent address changes while the box is ticked, copy again */
    for (var i = 0; i < names.length; i++) {
        document.getElementById("permanent_" + names[i]).addEventListener("input", function () {
            if (checkbox.checked) {
                copyAddress();
            }
        });
    }
}
