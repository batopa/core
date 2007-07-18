function checkOnSubmit(formName, r) {
    document.getElementById(errorsdiv).className = '';
    document.getElementById(errorsdiv).style.display = 'none';
    /*var alertType = document.getElementById('alertType').value;*/
    /*var alertType = 'innerHtml';*/
    /*var alertType = 'jsVar';*/
    var alertType = 'innerHtml';
    
    if (performCheck(formName, r, alertType)) {
        document.forms[formName].submit();
    } else if (alertType=='jsVar') {
        alert('jsErrors variable contains the array of errors:\n\n' +jsErrors + '\n\n Use this variable like you prefer!');
    }
}

jQuery.fn.extend({
  check: function() {
     return this.each(function() { this.checked = true; });
   },
  uncheck: function() {
     return this.each(function() { this.checked = false; });
   },
  toggleCheck: function() {
     return this.each(function() { this.checked = !this.checked ; });
   }
   
});

