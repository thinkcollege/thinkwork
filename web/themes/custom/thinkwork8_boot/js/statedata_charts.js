google.charts.load('current', { 'packages': ['corechart', 'table','line','geochart']});
jQuery.noConflict();
var statenametext = null;
var stringContent = null;
var yearstext = null;
var reportType = null;
var chartTitle = null;
var formatVAxis = null;
var tableStringContent = [];
var numtableStringContent = [];
var tableInsertContent = [];
var useRawNum = false;
var sheetName = [];
var query = null;
var tableDiv = null;
var reportHeading = [];
var formURL = [];
var formSheetname = [];
var formTitles = [];
var typeCount = null;
var countStates = null;
var countYears = null;
var countCats = null;
var clearAll = false;
var dataTable = null;
var reportURL = new URLSearchParams(window.location.search);
var columnSelected = null;
//var reportURL = ['payer','indst'];
// Document delegation for dynamically generated elements
columnSelected = jQuery('input[name="reportChoose"]:checked').val();


jQuery(document).on('click','.michRedraw', function() {
    jQuery('#sdchart_table_div_0').empty();
    jQuery('#chart_div_0').empty();
    var checkEmptySearch = reportURL.get('report') == 'payer' ? 'activity' : reportURL.get('report');
    var searchWarnings = '';

    console.log('Payers: ' + countChecks('payer') + '/Programs: ' + countChecks('program'));


    //if (countChecks(checkEmptySearch) > 0 && parseInt(countChecks('program') + countChecks('payer')) > 0){
    if(1 === 1) {
        jQuery('#tableWarnings').empty();
        var newChart = drawSDvisualization();
        jQuery('html, body').animate({
            scrollTop: jQuery("#chartTitle").offset().top
        }, 500);
        if(!jQuery('p.privWarn').hasClass('visible')) jQuery('p.privWarn').addClass('visible');
    } else {
        if (jQuery('#downCSV').hasClass('visible')) jQuery('#downCSV').removeClass('visible');
        searchWarnings += !countChecks(checkEmptySearch) > 0 ? 'Check some selections above for your search.' : '';
        searchWarnings += parseInt(countChecks('state')) < 1 ? '<br />Check some states at left for your search.' : 'Go fuck yourself';
        jQuery('#chart_div_0').append('<p class="searchWarnings">' + searchWarnings + '</p>');
        if(jQuery('p.privWarn').hasClass('privWarn')) jQuery('p.privWarn').removeClass('visible');

    }
});

jQuery(document).on('click','.tableSelect', function() {
  var tableIDval = "";
  

    dataTable = jQuery(this).val();
    valParentId = jQuery(this).closest('.collapse').attr('id');

    jQuery('img#chosenCheck').remove();
    jQuery('.tableSelect').each(function(i, obj) {
      jQuery(this).removeClass('tableChosen');
     if(jQuery(this).val() != dataTable) jQuery(this).prop('checked', false);
    if(!jQuery(this).closest('.collapse').is("#" + valParentId)) jQuery(this).closest('.collapse').removeClass('show');


    });

    //jQuery(this).prop('checked', true);
    jQuery(this).addClass('tableChosen');
    if(reportURL.get('report') == 'single')
  {
    var tableIndicator = '&nbsp;<img id="chosenCheck" src= "assets/green_check.png" alt="this table chosen" />';
    jQuery(this).parent('label.js-simple-tooltip').length ? jQuery(this).closest('label.js-simple-tooltip').append(tableIndicator) : jQuery('.tableChosen').after(tableIndicator);
  }
    console.log("Table: " + dataTable);
    var tableDescrip = jQuery(this).attr("data-simpletooltip-text") ? jQuery(this).attr("data-simpletooltip-text") : jQuery(this).closest('label').attr("data-simpletooltip-text");
    jQuery('input#tableId').val(dataTable);
    jQuery('input#tableDescrip').val(tableDescrip);
  
});



jQuery(document).on('click','input[name="reportChoose"]', function() {
  var tableIDval = "";
  var tableDescrip = "";

  if(jQuery(this).hasClass('acsVars')) {
    tableIDval = jQuery('input[name="acscat"]:checked').val();
    var tableDescrip = jQuery('input[name="acscat"]:checked').parent('label').attr("data-simpletooltip-text");

} else if (jQuery(this).hasClass('vrVars')) {
  tableIDval = jQuery('input[name="vrcat"]:checked').val();
  var tableDescrip = jQuery('input[name="vrcat"]:checked').parent('label').attr("data-simpletooltip-text");
  } else {
    tableIDval = jQuery(this).closest('div.altSelect').attr('id');
    var tableDescrip = jQuery(this).closest('.card').find('h5 button').attr("data-simpletooltip-text");

  }
  jQuery('input#tableId').val(tableIDval);
  jQuery('input#tableDescrip').val(tableDescrip);
  console.log("Table description: " + tableDescrip);
});





jQuery(document).on('change','select#rptPeriodContainer', function() {
    var getProgram = populatePrograms('programcull');
      });
jQuery(document).on('change','input[name="regionTrendPer[]"]', function() {
     trendInfo = checkTrends();

});
jQuery("form#sdChartForm").submit(function(event) {
    event.preventDefault(); // cancel default behavior


});

jQuery(document).on("change",'input:checkbox[name="regionPayer[]"]', function () {

   //console.log(payerarray1);
   //jQuery('#programContainer').empty();
      var getProgram = populatePrograms('programcull');


});
jQuery(document).on('click','#sdChartForm input.checkAll',function() {
    parentID = jQuery(this).closest('div.formSub').attr('id');
    //console.log(parentID);

    var checked = jQuery(this).prop('checked');
    jQuery('#' + parentID).find('ul input:checkbox').prop('checked', checked);
   // jQuery('#programContainer').empty();
   // var getProgram = populatePrograms('program');
});


// All the document ready stuff
jQuery(document).ready(function() {
    var singleActivRadio = singleActiveFilter();
    var checktrends = checkTrends();
    jQuery("#sdChartForm button.michRedraw").click(function(event) {
       event.preventDefault(); // cancel default behavior


    });
    var grpNameval = getUrlString('grp');
    jQuery('input#grpName').val(grpNameval);

    updateSelectCount('state');
    updateSelectCount('year');

    jQuery('#collapseSeven input').click(function(event) {
        updateSelectCount('state');

    });
    jQuery('#collapseEight input').click(function(event) {
        updateSelectCount('year');

    });
    jQuery('#collapseNine input').click(function(event) {
        updateSelectCount('year');

    });

    jQuery("#sdChartForm button.chooseControl").click(function(event) {
        event.preventDefault(); // cancel default behavior


     });


     jQuery("#sdChartForm button#clearForm").click(function(event) {
        event.preventDefault(); // cancel default behavior


     });


    programSlug = reportURL.get('grp') == 'natrep' ? 'Programs' : 'CMHSPs';
    console.log('Group from URL: ' + reportURL.get('grp'));
    jQuery('span.programSlug').text(programSlug);
    var pageSub = reportURL.get('grp') == 'indst' ? 'Individual State Report' : (reportURL.get('grp') == 'stcomp' ? 'State Comparison Report' : (reportURL.get('grp') == 'natrep' ? 'National Report' : ''));
    if (jQuery('#downCSV').hasClass('visible')) jQuery('#downCSV').removeClass('visible');
    if(jQuery('p.privWarn').hasClass('privWarn')) jQuery('p.privWarn').removeClass('visible');
    jQuery('h1#chartTitle').text('State Data Chart Builder: ' + pageSub);
    jQuery('a#singleTab').attr('href','./data.html?report=single');
    jQuery('a#comparisonTab').attr('href','./data.html?report=comparison');
    jQuery('a#nationalTab').attr('href','./data.html?report=national');
    if(jQuery('#buttonRow a').hasClass('active')) jQuery('#buttonRow a').removeClass('active');
    jQuery('#buttonRow a.btn-' + reportURL.get('grp')).addClass('active');
    jQuery('#buttonRow a.btn-indst').attr('href','./data.html?report=' + reportURL.get('report') + '&grp=indst' );
    jQuery('#buttonRow a.btn-stcomp').attr('href','./data.html?report=' + reportURL.get('report') + '&grp=stcomp' );
    jQuery('#buttonRow a.btn-natrep').attr('href','./data.html?report=' + reportURL.get('report') + '&grp=natrep' );
    jQuery('#downCSV').on('click',function() {
        downloadCSV();

    });
    var rptperiodarray  = reportURL.get('report') == 'trend' ? populateRptPeriod('check') : populateRptPeriod('select');


    jQuery('#trendActivityContainer input[name="trendActivityCat"]').on('change',function() {
        var singleActiv = jQuery('#trendActivityContainer input[name="trendActivityCat"]:checked').val();
        singleActiveFilter();

    });
    jQuery('#sdChartForm input.checkAll').prop('checked',false);
    jQuery('#sdChartForm input.progToggle').prop('checked',false);
    //jQuery('#programHed').hide();
    //var payerarray = populatePayers(reportURL.get('grp'));
    var racearray = populateRace();
    var ethnicarray = populateEthnic();

    var getProgram = populatePrograms('program');
    var getSingle = reportURL.get('report') == 'single' ? populateSingle() : null;

    if(reportURL.get('report') == 'single') {

        jQuery('.chartsection .altSelect').removeClass('showVis');
        jQuery('button.tableSelect').removeAttr('data-target');
        jQuery('.chartsection .altSelect.singleSelect').removeClass('showVis');
        jQuery('#collapseEight .altSelect.singleSelect').removeClass('showVis');
        jQuery('#collapseEight .altSelect.multiSelect').addClass('showVis');
        jQuery('#collapseSeven .altSelect.multiSelect').removeClass('showVis');
        jQuery('#collapseSeven .altSelect.singleSelect').addClass('showVis');
        jQuery('.stateSection').removeClass('natRemove');

    } else if (reportURL.get('report') == 'comparison') {
        jQuery('.chartsection .altSelect').removeClass('showVis');
        jQuery('.chartsection .altSelect.multiSelect').addClass('showVis');
        jQuery('#collapseSeven .altSelect.multiSelect').addClass('showVis');
        jQuery('#collapseSeven .altSelect.singleSelect').removeClass('showVis');
        jQuery('#collapseOne .altSelect.singleSelect').addClass('showVis');
        jQuery('#collapseTwo .altSelect.singleSelect').addClass('showVis');
        jQuery('#collapseThree .altSelect.singleSelect').addClass('showVis');
        jQuery('#collapseFour .altSelect.singleSelect').addClass('showVis');
        jQuery('#collapseFive .altSelect.singleSelect').addClass('showVis');
        jQuery('#collapseSix .altSelect.singleSelect').addClass('showVis');


    } else if(reportURL.get('report') == 'national') {
        jQuery('.chartsection .altSelect').removeClass('showVis');
        jQuery('.chartsection .altSelect.singleSelect').addClass('showVis');
        jQuery('#collapseEight .altSelect.multiSelect').removeClass('showVis');
        jQuery('#collapseEight .altSelect.singleSelect').addClass('showVis');
        jQuery('.chartsection.stateSection').addClass('natRemove');


    }
    collapseEight
    if(reportURL.get('report') == 'payer') {
        if(jQuery(' main .formSub').hasClass('selectShow')) jQuery('main .formSub').removeClass('selectShow');
         if(jQuery('.tabSelect').hasClass('selected')) jQuery('.tabSelect').removeClass('selected');
        if(!jQuery('#allactivityDiv').hasClass('selectShow')) jQuery('#allactivityDiv').addClass('selectShow');
         if(!jQuery('#payerTab').hasClass('selected')) jQuery('#payerTab').addClass('selected');
         if(!jQuery('#rptPeriodDiv').hasClass('active')) jQuery('#rptPeriodDiv').addClass('active');

    } else if(reportURL.get('report') == 'single') {
        if(jQuery('main .formSub').hasClass('selectShow')) jQuery('main .formSub').removeClass('selectShow');
        if(jQuery('.tabSelect').hasClass('selected')) jQuery('.tabSelect').removeClass('selected');
        if(!jQuery('#singleTab').hasClass('selected')) jQuery('#singleTab').addClass('selected');
        if(!jQuery('#singleDiv').hasClass('selectShow')) jQuery('#singleDiv').addClass('selectShow');
        if(!jQuery('#rptPeriodDiv').hasClass('active')) jQuery('#rptPeriodDiv').addClass('active');
    } else if(reportURL.get('report') == 'national') {
        if(jQuery('main .formSub').hasClass('selectShow')) jQuery('main .formSub').removeClass('selectShow');
        if(jQuery('.tabSelect').hasClass('selected')) jQuery('.tabSelect').removeClass('selected');
        if(!jQuery('#nationalTab').hasClass('selected')) jQuery('#nationalTab').addClass('selected');
        if(!jQuery('#nationalDiv').hasClass('selectShow')) jQuery('#nationalDiv').addClass('selectShow');
        if(jQuery('#rptPeriodDiv').hasClass('active')) jQuery('#rptPeriodDiv').removeClass('active');
    }
    jQuery('#programHed input:checkbox').on('change',function() {
        if(jQuery(this).prop('checked')) {
                if (jQuery('#programContainer').hasClass('hideMe')) {jQuery('#programContainer').removeClass('hideMe');
                }
                if(reportURL.get('report') == 'national' && jQuery('input[name="regionTrendPer[]"]:checked').length < 2) {
                    if (!jQuery('#noDates').length) jQuery('#programHed').after('<p class="searchWarnings sWsmaller clearable" id="noDates">Select at least two reporting periods at top right to begin searching by program</p>');
                    jQuery('input.progToggle').prop('checked', false);

                } else {
                    if (jQuery('#noDates').length) {
                        jQuery('#noDates').remove();
                    }
                    populatePrograms('programcull');
                }
            }
            else {
                    if (!jQuery('#programContainer').hasClass('hideMe')) {
                        jQuery('#programContainer').addClass('hideMe') ;
                        jQuery('#programContainer').find('input:checkbox').prop('checked', false);
                    }
                }
        });










    // var updateSelections = readSelections();
    var checkState = countChecks('state');
    var checkYears = countChecks('year');
    var checkCats = countChecks('category');
    var checkPrograms = countChecks('program');
    var checkPayers = countChecks('payer');

    jQuery("#chartRedraw").click(function() {
        jQuery('html, body').animate({
            scrollTop: jQuery("#chartTitle").offset().top
        }, 500);
    });

    //if(useRawNum == true) { if(!jQuery('.percOn').hasClass('toggleHide')) jQuery('.percOn').addClass('toggleHide'); if(jQuery('.numOn').hasClass('toggleHide')) jQuery('.numOn').removeClass('toggleHide');} else { if(!jQuery('.numOn').hasClass('toggleHide')) jQuery('.numOn').addClass('toggleHide'); if(jQuery('.percOn').hasClass('toggleHide')) jQuery('.percOn').removeClass('toggleHide');}

    jQuery('.switchNum button').on('click', function() {
        useRawNum =  useRawNum == true ? false : true;
        //console.log(useRawNum);
        if(useRawNum == true) {
            if(!jQuery('.percOn').hasClass('toggleHide')) jQuery('.percOn').addClass('toggleHide'); if(jQuery('.numOn').hasClass('toggleHide')) jQuery('.numOn').removeClass('toggleHide'); jQuery('.numOn').addClass('toggleShow');
        } else
        {
             if(!jQuery('.numOn').hasClass('toggleHide')) jQuery('.numOn').addClass('toggleHide'); if(jQuery('.percOn').hasClass('toggleHide')) jQuery('.percOn').removeClass('toggleHide'); jQuery('.percOn').addClass('toggleShow');
        }

    });

    jQuery('input[name="dlChoose"]').on('click', function() {
        var dlType = jQuery('input[name="dlChoose"]:checked').val();
        useRawNum = dlType == 'Rawnumber' ? true : false;
        //var redraw = drawSheetName();

    });

    jQuery("#clearForm").click(function() {
            jQuery('#accordion input').prop('checked', false);
            jQuery('#allactivityDiv input').prop('checked', false);
            jQuery('#singleDiv input').prop('checked', false);
            jQuery('#nationalDiv input').prop('checked', false);
            jQuery('#accordion #genderSelect').prop('selectedIndex',0);
            jQuery('#accordion #ags').prop('selectedIndex',0);
            jQuery('#accordion #age').prop('selectedIndex',0);
            jQuery('#chart_div_0').empty();
            jQuery('#legend_div').empty();
            jQuery('#sdchart_table_div_0').empty();
            jQuery('#nationalTitle').empty();
            jQuery('#sdchart_table_div_0_title').empty();
            jQuery('#downCSV').removeClass('visible');
            jQuery('.privWarn').removeClass('visible');

            clearAll = true;


        jQuery('html, body').animate({
            scrollTop: jQuery("#chartTitle").offset().top
        }, 500);
    });

    jQuery('input.checkAll').click(function() {
        parentID = jQuery(this).closest('div.collapse').attr('id');
        // console.log(parentID);

        var checked = jQuery(this).prop('checked');
        jQuery('#' + parentID).find('.col input:checkbox').prop('checked', checked);
    });

    jQuery('select').change(countChecks);
    jQuery('#accordion label').accessibleSimpleTooltipAria({
        // simpletooltipText: 'title'
    });
    jQuery("#chartSelector").submit(function(event) {
     //   event.preventDefault(); // cancel default behavior


    });

    jQuery("#sdChartForm").submit(function(event) {
     //   event.preventDefault(); // cancel default behavior


    });
    //updateSelectCount('state');
   // updateSelectCount('year');



    jQuery('#printButton').click(function() {
    window.print();
    });



    jQuery('#collapseSeven input').click(function(event) {
        updateSelectCount('state');

    });
    jQuery('#collapseEight input').click(function(event) {
        updateSelectCount('year');

    });
    jQuery('#collapseNine input').click(function(event) {
       updateSelectCount('year');

    });
    jQuery('input.yearCheck').click(function(event) {
       updateSelectCount('year');

    });
    jQuery('input.stateCheck').click(function(event) {
       updateSelectCount('state');

    });

    jQuery('#accordion .chartselection input').click(function() {
        jQuery("input[type='radio']:checked").each(function() {
            var catIdValue = jQuery(this).attr("id");
            updateCatNameText(catIdValue);
        });
    });

    // if (checkState != 0 && checkYears != 0 && checkCats != 0) google.charts.setOnLoadCallback(drawSheetName);
    var reportType = getUrlString('report');
    // end of document.ready functions

});
function checkTrends() {
    countDates = countChecks('trendPers');
     console.log(countDates);
     if(countDates < 2) {
         if(jQuery('#nationalActivityDiv').hasClass('active')) jQuery('#nationalActivityDiv').removeClass('active');
         if(jQuery('#singleActivity').hasClass('active')) jQuery('#singleActivity').removeClass('active')
        }
     else if (countDates >= 2) {
         if(!jQuery('#nationalActivityDiv').hasClass('active')) jQuery('#nationalActivityDiv').addClass('active');
         if(!jQuery('#singleActivity').hasClass('active') && jQuery('#nationalActivityContainer input[name="nationalActivityCat"]:checked').val()) jQuery('#singleActivity').addClass('active');
        }

}

function singleActiveFilter() {
    var singleActiv = jQuery('#nationalActivityContainer input[name="nationalActivityCat"]:checked').val();
    if(!jQuery('#singleActivity').hasClass('active') && singleActiv) jQuery('#singleActivity').addClass('active');
    if(jQuery('#nationalActivitySelContainer li').hasClass('active')) jQuery('#nationalActivitySelContainer li').removeClass('active');
    if(singleActiv == '1' || singleActiv == '2' || singleActiv == '4') {
        if(!jQuery('#nationalActivitySelContainer li').hasClass('active')) jQuery('#nationalActivitySelContainer li').addClass('active');
    }
    else if(singleActiv == '3' || singleActiv == '98' ) {
        if(jQuery('#nationalActivitySelContainer li.limited').hasClass('active')) jQuery('#nationalActivitySelContainer li.limited').removeClass('active');
        if(!jQuery('#nationalActivitySelContainer li.unlimited').hasClass('active')) jQuery('#nationalActivitySelContainer li.unlimited').addClass('active');
    }
}


function getCheckboxLabels(type) {


    
    var choiceNames = '';
    if (type == 'comparison') {
         var progId = jQuery('input[name="reportChoose"]:checked').attr("id");
         var progText = jQuery("label[for='"+progId+"']").text();
         progPrefix = jQuery('input[name="reportChoose"]:checked').closest('.card').find('.tableChosen').attr("data-simpletooltip-text") ? jQuery('input[name="reportChoose"]:checked').closest('.card').find('.tableChosen').attr("data-simpletooltip-text") : jQuery('input[name="reportChoose"]:checked').closest('.card').find('.tableChosen').closest('label').attr("data-simpletooltip-text");
         choiceNames = progPrefix + ": " + progText;
    }
    else if (type == 'single') {
        choiceNames = [];
        jQuery('.tableChosen').each(function() {
            var tableDescrip = jQuery(this).attr("data-simpletooltip-text") ? jQuery(this).attr("data-simpletooltip-text") : jQuery(this).closest('label').attr("data-simpletooltip-text");
           
            choiceNames.push(tableDescrip);

        });
    }
    console.log('Table descripff: ' + choiceNames);
    return choiceNames;

}
function getAgeLabel() {
    agestart = jQuery('#ags option:selected').val();
    ageend = jQuery('#age option:selected').val();


    var ageText = parseInt(agestart) === 0 && parseInt(ageend) === 108 ? 'Ages: all' : 'Ages: ' + agestart + ' to ' + ageend

    return ageText;

}
function getRptPerLabel() {
    var rptPerLabel = jQuery('#rptPeriodContainer').find(":selected").text();
    return rptPerLabel;

}
function getGenderLabel() {
    gender = jQuery('#genderSelect option:selected').text();


    var genderText = 'Gender: ' + gender;

    return genderText;

}

function populateRace() {


    jQuery.ajax({
        url: '/statedata_charts/php/sd_chartcall.php?type=race',
        type: "POST",
        dataType: "json",
        cache: false,
        success: function(data) {
            console.log(data);
            jQuery.each(data, function () {
                var options = "";
                options += '<li><label>' + '<input type="checkbox" value="' + this.field_choice_code + '" name="regionRace[]" /><span class="textLabel">' + this.field_choice_label + '</span></label></li>';
                    jQuery("#raceContainer")
                    .append(options
                    )
            })



        },
        error: function(xhr, status, err) {
        console.log('error')
        }

    });


}

function populateSingle() {
    if(!jQuery('#singleTab').hasClass('selected')) jQuery('#singleTab').addClass('selected');

    if(!jQuery('#singleDiv').hasClass('selectShow')) jQuery('#singleDiv').addClass('selectShow');
    jQuery.ajax({
        url: '/statedata_charts/php/sd_chartcall.php?type=nonPop',
        type: "POST",
        dataType: "json",
        cache: false,
        success: function(data) {
            //console.log(data);
            jQuery.each(data, function () {
                var options = "";
                options += '<li><label>' + '<input type="checkbox" value="' + this.field_choice_code + '" name="regionSingle[]" /><span class="textLabel">' + this.field_choice_label + '</span></label></li>';
                jQuery("#singleContainer").append(options
                    )
            })



        },
        error: function(xhr, status, err) {
        console.log('error')
        }

    });



}
function populateEthnic() {


    jQuery.ajax({
        url: '/statedata_charts/php/sd_chartcall.php?type=ethnic',
        type: "POST",
        dataType: "json",
        cache: false,
        success: function(data) {
            console.log(data);
            jQuery.each(data, function () {
                var options = "";
                options += '<li><label>' + '<input type="checkbox" value="' + this.field_choice_code + '" name="regionEthnic[]" /><span class="textLabel">' + this.field_choice_label + '</span></label></li>';
                    jQuery("#ethnicContainer")
                    .append(options
                    )
            })



        },
        error: function(xhr, status, err) {
        console.log('error')
        }

    });

}

function populateRptPeriod(type) {
    var months = [ "January", "February", "March", "April", "May", "June",
           "July", "August", "September", "October", "November", "December" ];


    jQuery.ajax({
        url: '/statedata_charts/php/sd_chartcall.php?type=rptperiod&selector=' + type,
        type: "POST",
        dataType: "json",
        cache: false,
        success: function(data) {
            jQuery.each(data, function () {
                //var formattedDate = new Date(this.rptperiod);
                //var yearbase = new Date(this.rptperiod.substr(0,4)).getFullYear();
                var m =  parseInt(this.rptperiod.substr(5,2));
                m -= 1;  // JavaScript months are 0-11
                // Has this changed?
                var y = this.rptperiod.substr(0,4);
                var monthname = months[m];


               var dateOption = monthname + ", " + y;
                var options = '';
                if(type == 'select') {
                options += '<option value="' + this.rptperiod + '">' + dateOption + '</option>';
                    jQuery("#rptPeriodContainer")
                    .append(options
                    )
                } else if(type == 'check') {
                    options += "<li><label>" + '<input type="checkbox" value="' + this.rptperiod + '" name="regionnationalPer[]"><span class="textLabel">' + dateOption + '</span></label></li>';
                    jQuery('#nationalPerContainer').append(options);

                }
            })



        },
        error: function(xhr, status, err) {
        console.log('error')
        }

    });

}
function populatePrograms(slug,tableindex, numpercdol ) {


    if (slug == 'program') { jQuery.ajax({
            url: '/statedata_charts/php/sd_chartcall.php?type=' + slug,
            type: "POST",
          //data: jQuery('#sdChartForm').serialize(),
            dataType: "json",
            cache: false,
            success: function(data) {

            jQuery.each(data, function () {
                    var $options = "";
                    $options += "<li><label>" + '<input type="checkbox" value="' +  + this.program + '" name="regionProgram[]"><span class="textLabel">' + this.field_choice_label + ' (' + this.countinds + ')</span></label></li>';
                        jQuery("#programContainer")
                        .append($options
                        );

                })
               // if (!jQuery.trim(data)){ jQuery('#programHed').hide(); console.log('fuggabe');} else { jQuery('#programHed').show();}




            },
            error: function(xhr, status, err) {
            console.log('error')
            }
        });
    }
    else if (slug == 'programcull') {
        var viewSlug = reportURL.get('report') ? reportURL.get('report') : 'comparison';
        var grpSlug = reportURL.get('grp') ? reportURL.get('grp') : 'indst';
        jQuery("#programContainer").empty();
        jQuery.ajax({
        url: '/statedata_charts/php/sd_chartcall.php?type=' + slug + '&grp=' + grpSlug + '&view=' + viewSlug,
        type: "POST",
        data: jQuery('#sdChartForm').serialize(),
        dataType: "json",
        cache: false,
        success: function(data) {

        jQuery.each(data, function () {

                var $options = "";
                $options += "<li><label>" + '<input type="checkbox" value="' +  + this.program + '" name="regionProgram[]"><span class="textLabel">' + this.field_choice_label + ' (' + this.countinds + ')</span></label></li>';
                    jQuery("#programContainer")
                    .append($options
                    )

            })




        },
        error: function(xhr, status, err) {
        console.log('error')
        }
    });
    }

    else if(slug == 'table') {
        var reportType = reportURL.get('report') ? reportURL.get('report') : 'comparison';
        var storedID = reportURL.get('storedid') ? '&storedid =' + reportURL.get('storedid') : '';
        if(reportType == 'single') {
            var returnedStuff1 =
            jQuery.ajax({
                url: '/statedata_charts/php/sd_chartcall.php?reportType=' + reportType + '&type=table&singletype=num' + storedID,
                beforeSend: function(){
                    jQuery("#sdchart_table_div_0").html('<img class="loadingImg" src="/michigan/assets/spinner.gif">');
                },
                type: "POST",
                data: jQuery('#sdChartForm').serialize(),
                dataType: "json",
                global: false,
                async:false,
                cache: false,
                success: function(data) {

                return data;

                }
            //  ,
            //   error: function(xhr, status, err) {
                //    console.log('table error')
            //   }
            }).responseText;

            var returnedStuff2 =
            jQuery.ajax({
                url: '/statedata_charts/php/sd_chartcall.php?reportType=' + reportType + '&type=table&singletype=perc' + storedID,
                beforeSend: function(){
                    jQuery("#sdchart_table_div_1").html('<img class="loadingImg" src="/michigan/assets/spinner.gif">');
                },
                type: "POST",
                data: jQuery('#sdChartForm').serialize(),
                dataType: "json",
                global: false,
                async:false,
                cache: false,
                success: function(data) {

                return data;

                }
            //  ,
            //   error: function(xhr, status, err) {
                //    console.log('table error')
            //   }
            }).responseText;

            var returnedStuff3 =
            jQuery.ajax({
                url: '/statedata_charts/php/sd_chartcall.php?reportType=' + reportType + '&type=table&singletype=dol' + storedID,
                beforeSend: function(){
                    jQuery("#sdchart_table_div_2").html('<img class="loadingImg" src="/michigan/assets/spinner.gif">');
                },
                type: "POST",
                data: jQuery('#sdChartForm').serialize(),
                dataType: "json",
                global: false,
                async:false,
                cache: false,
                success: function(data) {

                return data;

                }
            //  ,
            //   error: function(xhr, status, err) {
                //    console.log('table error')
            //   }
            }).responseText;


            var returnedStuff = [returnedStuff1,returnedStuff2,returnedStuff3];
        } else {

            var returnedStuff =
            jQuery.ajax({
                url: '/statedata_charts/php/sd_chartcall.php?reportType=' + reportType + '&type=table' + storedID,
                beforeSend: function(){
                    jQuery("#sdchart_table_div_0").html('<img class="loadingImg" src="/michigan/assets/spinner.gif">');
                },
                type: "POST",
                data: jQuery('#sdChartForm').serialize(),
                dataType: "json",
                global: false,
                async:false,
                cache: false,
                success: function(data) {

                return data;

                }
            //  ,
            //   error: function(xhr, status, err) {
                //    console.log('table error')
            //   }
            }).responseText;
        }

        console.log( 'in function: ' + returnedStuff);
        return returnedStuff;

    } 

    else if(slug == 'chart') {
        var reportType = reportURL.get('report') ? reportURL.get('report') : 'comparison';
        var storedID = reportURL.get('storedid') ? '&storedid =' + reportURL.get('storedid') : '';
        var changeChart = tableindex === 0 || tableindex ? '&tableindex=' + tableindex + '&numpercdol=' + numpercdol : '';
        
        if(reportType == 'single') {
            
            if((tableindex ===0 || tableindex) && numpercdol) {
                var chartDivIndex = numpercdol == 'num' ? '#chart_div_0' :(numpercdol == 'perc' ? '#chart_div_1' : '#chart_div_2');

                var returnedStuff1 =
                jQuery.ajax({
                    url: '/statedata_charts/php/sd_chartcall.php?reportType=' + reportType + '&type=chart&singletype=' + numpercdol + changeChart + storedID,
                    beforeSend: function(){
                        jQuery(chartDivIndex).html('<img class="loadingImg" src="/michigan/assets/spinner.gif">');
                    },
                    type: "POST",
                    data: jQuery('#sdChartForm').serialize(),
                    dataType: "json",
                    global: false,
                    async:false,
                    cache: false,
                    success: function(data) {

                    return data;

                }
                 ,
                  error: function(xhr, status, err) {
                      return false;
                   }
                }).responseText;

            } else
            {
                var returnedStuff1 =
                jQuery.ajax({
                    url: '/statedata_charts/php/sd_chartcall.php?reportType=' + reportType + '&type=chart&singletype=num' + changeChart + storedID,
                    beforeSend: function(){
                        jQuery("#chart_div_0").html('<img class="loadingImg" src="/michigan/assets/spinner.gif">');
                    },
                    type: "POST",
                    data: jQuery('#sdChartForm').serialize(),
                    dataType: "json",
                    global: false,
                    async:false,
                    cache: false,
                    success: function(data) {

                    return data;

                    }
                  ,
                   error: function(xhr, status, err) {
                       return false;
                   }
                }).responseText;

            var returnedStuff2 =
                jQuery.ajax({
                    url: '/statedata_charts/php/sd_chartcall.php?reportType=' + reportType + '&type=chart&singletype=perc' + storedID,
                    beforeSend: function(){
                        jQuery("#chart_div_1").html('<img class="loadingImg" src="/michigan/assets/spinner.gif">');
                    },
                    type: "POST",
                    data: jQuery('#sdChartForm').serialize(),
                    dataType: "json",
                    global: false,
                    async:false,
                    cache: false,
                    success: function(data) {

                    return data;

                    }
                  ,
                   error: function(xhr, status, err) {
                        console.log('table error');
                   }
                }).responseText;

                var returnedStuff3 =
                jQuery.ajax({
                    url: '/statedata_charts/php/sd_chartcall.php?reportType=' + reportType + '&type=chart&singletype=dol' + storedID,
                    beforeSend: function(){
                        jQuery("#chart_div_2").html('<img class="loadingImg" src="/michigan/assets/spinner.gif">');
                    },
                    type: "POST",
                    data: jQuery('#sdChartForm').serialize(),
                    dataType: "json",
                    global: false,
                    async:false,
                    cache: false,
                    success: function(data) {

                    return data;

                    }
                 ,
                  error: function(xhr, status, err) {
                      console.log('table error')
                      return false;
                  }
                }).responseText; 
            }

            console.log( 'in function: ' + returnedStuff);
            var returnedStuff = tableindex && numpercdol ? [returnedStuff1] : [returnedStuff1,returnedStuff2,returnedStuff3];
        } else {
            

                var returnedStuff =
                jQuery.ajax({
                    url: '/statedata_charts/php/sd_chartcall.php?reportType=' + reportType + '&type=chart&' + storedID,
                    beforeSend: function(){
                        jQuery('#chart_div_0').html('<img class="loadingImg" src="/michigan/assets/spinner.gif">');
                    },
                    type: "POST",
                    data: jQuery('#sdChartForm').serialize(),
                    dataType: "json",
                    global: false,
                    async:false,
                    cache: false,
                    success: function(data) {

                    return data;

                }
                 ,
                  error: function(xhr, status, err) {
                      return false;
                   }
                }).responseText;

        }
        console.log( 'in function: ' + returnedStuff);
        return returnedStuff;

    } 



}
function downloadCSV() {
    var grpSlug = reportURL.get('grp') ? reportURL.get('grp') : 'indst';
    var totalPrograms =countChecks('program');
    var totalComparison =countChecks('comparison');
    var viewSlug = reportURL.get('report') ? '&view=' + reportURL.get('report') : '&view=comparison';
   // var catType = totalPayers > 0 ? (totalPrograms > 0 ? '&category=catmixed':'&category=catpay'): (totalPrograms > 0 ? '&category=catprog' : '');
    var url = 'php/sd_chartcall.php?type=download';
    jQuery('#sdChartForm').attr('action', url);
    var HiddenTtl = buildTableTitle('download');
    console.log(HiddenTtl);
    jQuery('#titleInputs').append('<input type="hidden" name="sendTitle" value="' + HiddenTtl + '" />');
     var form = jQuery(this);
    jQuery.ajax({
               type: "POST",
               url: url,
               data: form.serialize(),
               success: function(data)
               {
                  // console.log(data);
               }
             });

}

function getUrlString() {
    returnVar = [];
    jQuery.urlParam = function (name) {
        var results = new RegExp('[\?&]' + name + '=([^&#]*)')
                          .exec(window.location.search);

        return (results !== null) ? results[1] || 0 : false;
    }

        var returnVar1 = jQuery.urlParam('report') ? jQuery.urlParam('report') : 'comparison';
        var returnVar2 = jQuery.urlParam('grp') ? jQuery.urlParam('grp') : 'indst';
        returnVar.push(returnVar1);
        returnVar.push(returnVar2);

   /* for (i = 0; i < reportVariables.length; i++) {
        reportName = reportVariables[i].split('=');

        if (reportName[0] === reportVars) {
            return reportName[1] === undefined ? true : reportName[1];
        }
    } */


    return returnVar;

}


// checkbox count
function countChecks(typeCount) {
    if (!typeCount) typeCount = false;
    var countStates = reportURL.get('report') == 'comparison' ? jQuery('input[name="regionStates[]"]:checked').length : (jQuery('input[name="regionStatesInd"]').is(':checked') ? 1 : 0) ;
    var countYears = jQuery('input[name="regionYear[]"]:checked').length;
    var countTrendPers = jQuery('input[name="regionTrendPer[]"]:checked').length;
    var checkCount = countStates * countYears;
    var catCount = jQuery('input[name="reportChoose"]:checked').val() ? 1 : 0;
    var trendCat = jQuery('#trendActivityContainer input[name="trendActivityCat"]:checked').val();
    var trendSel = jQuery('#trendActivitySelContainer input[name="trendActivitySel"]:checked').val();
    var showStatewide = jQuery('input[name="showStatewide[]"]:checked').length;
    var returnCount;
    switch (typeCount) {
        case 'state':
            returnCount = countStates;
            break;

        case 'trendPers':
            returnCount = countTrendPers;
            break;
        case 'trend':
            returnCount = countTrendPers >= 2 && trendCat && trendSel ? 1 : 0;
            break;
        case 'statewide':
            returnCount = showStatewide;
            break;
        default :
            returnCount = null;
            break;
    }

   return returnCount;
}
// counting states and years
function updateSelectCount(checkType) {
    if (checkType == 'year') {
        yeararray1 = getStateYearArray('year');

        jQuery('#yearCountText').empty();
        jQuery('#summyearCountText').empty();
        var yrsSelect = yearstext;
        if (yearstext.length > 20) {
            yrsSelect = "Selected: (" + countChecks('year') + ") ";
        }
        yrsSelectText = yrsSelect;
        jQuery('#yearCountText').append(yrsSelectText);
        jQuery('#summyearCountText').append(yrsSelectText);

    }

}

function updateCatNameText() {
    jQuery('#catChecked').empty();


}

function getStateYearArray(choiceType) {

    var statearray1 = [];
     var yeararray1 = [];
    var statename1 = jQuery('input:checkbox[name="regionStates[]"]:checked').each(function() {
        var stateName = jQuery(this).closest('label').text();
        statearray1.push(stateName);
    });
    var statename2= jQuery('input:radio[name="regionStatesInd"]:checked').each(function() {
        statearray1.push(jQuery(this).val());
    });
    var years1 = reportURL.get('grp') == 'natrep' ? jQuery('input[name="summChoose"]:checked').each(function() {
        yeararray1.push(jQuery(this).val());
    }) : jQuery('input:checkbox[name="regionYear[]"]:checked').each(function() {
        yeararray1.push(jQuery(this).val());
    });


    return choiceType == 'year' ? yeararray1 : statearray1;

}





var legends = [
    ['Vision', 'Hearing', 'Speech', 'Learning', 'Mobility', 'Daily living', 'Environmental', 'Vehicle', 'Computers', 'Recreation'],
    ['Indivs. w/ disabilities', 'Family members', 'Reps. of education', 'Reps. of employment', 'Reps. of health', 'Reps. of community living', 'Reps. of technology'],
    ['Highly satisfied', 'Satisfied', 'Satisfied somewhat', 'Not satisfied'],
    ['Assist in decision-making', 'Serve as loaner', 'Provide accommodation', 'Training'],
    ['Products', 'Funding', 'Technology', 'Combination', 'Transition']

];

function legendBuild(legendIn) {
    var legendOut = [];
    var numCount = legendIn.length;
    if (numCount < 1) return;

    for (i = 0; i < numCount; i++) {
        legendOut.push('<div class="legendElem"><div class="legendColorBlock legendNum' + i + '"><img src="/michigan/assets/legend' + i + '.png" /></div><div class="legendText">' + legendIn[i] + '</div></div>');

    }
    return legendOut;
}
function buildTableTitle(type) {
    var breaker = type == 'download' ? ' | ' : ' | ';
    var reportType = reportURL.get('report');
    var tableTitle = getCheckboxLabels(reportType);
    var joinTitle = 'Search results for: ';
    var tableSelected = '';
    
    joinTitle += tableTitle + ' in ' + getStateYearArray('state') + ' during years: ' + getStateYearArray('year') ;
    //console.log("Payer names: ");
    //console.log(payerNames);
    return joinTitle;
}

jQuery(document).on('click','.singletable tr', function() {
    var reportType = reportURL.get('report');
    var tableindex = jQuery("tr", jQuery(this).closest("tbody")).index(this);
    var chartfind = jQuery(this).closest('.singletable');
    var charttype = chartfind.hasClass('num') ? 'num' :(chartfind.hasClass('perc') ? 'perc': 'dol');
    console.log("Table index: " + tableindex);
    if(reportType == 'single') drawSDvisualization(true,tableindex,charttype);


});

function drawSDvisualization(changeChart,tableindex,numpercdol) {
    var titleDiv = jQuery('#sdchart_table_div_0_title');
    var stackedType = 'percent';
    if(!changeChart){

        jQuery(titleDiv).empty();
        jQuery('#sdchart_table_div_0').empty();
        jQuery('#sdchart_table_div_1').empty();
        jQuery('#sdchart_table_div_2').empty();

        jQuery('.clearable').empty();
    } 
    if(!numpercdol || numpercdol == 'num')jQuery('#chart_div_0').empty();
    if(!numpercdol || numpercdol == 'perc') jQuery('#chart_div_1').empty();
    if(!numpercdol || numpercdol == 'dol')jQuery('#chart_div_2').empty();
    var legendArray = reportURL.get('report') == 'single' ? getCheckboxLabels('single') : getCheckboxLabels('comparison');
    //console.log(legendArray);
    var legendHTML = legendBuild(legendArray);
    if((reportURL.get('report') == 'single' && countChecks('single') && countChecks('single') < 12) || (reportURL.get('report') == 'comparison' && countChecks('activity') && countChecks('activity') < 5)) { stackedType = true ; }

    var chartReturn = null; 
    var container = null;
    var inputCount = parseInt(countChecks('program') + (countChecks('program') > 0 ? 0 : countChecks('comparison')));
    var chartHeight = inputCount > 3 ? (inputCount > 5 ? (inputCount > 9 ? '900' : '600') : '500') : '250';
    var groupWid = inputCount > 3 ? (inputCount > 5 ? '22' : '32') : '22';
    
    var chartTitle = reportURL.get('report') == 'single' ? buildTableTitle('single') : buildTableTitle('comparison');
    //console.log('Chart title: ' + chartTitle);
    
    var chartoptions = {
    title: chartTitle,
    width: '90%',
    height: '600',
    vAxis: {minValue: 0},
    colors: ['#006e82', '#fae6be', '#00a0fa', '#8214a0', '#a0fa82', '#fa7850', '#005ac8', '#f0f032', '#0ab45a', '#000000','#f5b7b1','#85929e']
    };
    var chartdata = null;
    var chart = null;
    var containerDiv = null;
    if(reportURL.get('report') == 'single'){
        var joinTitle = buildTableTitle('single');
        var chartReturn = populatePrograms('chart',tableindex,numpercdol); 
        
        if(changeChart && numpercdol){
            var tableNum = numpercdol == 'num' ? '0' : (numpercdol == 'perc' ? '1' : '2');
            if(numpercdol == 'perc') {
                chartoptions = {
                    title: chartTitle,
                    width: '90%',
                    height: '600',
                    vAxis: {minValue: 0,format: '#\'%\''},
                    colors: ['#006e82', '#fae6be', '#00a0fa', '#8214a0', '#a0fa82', '#fa7850', '#005ac8', '#f0f032', '#0ab45a', '#000000','#f5b7b1','#85929e']
                };
             }
            
            containerDiv = 'chart_div_' + tableNum;
            container = document.getElementById(containerDiv);
            chartdata = new google.visualization.DataTable(chartReturn[0]);
            chart = new google.visualization.ColumnChart(container);
            if(numpercdol == 'perc') {
                var obj = JSON.parse(chartReturn[0]);
                console.log('Data length: ' + obj.cols.length);
            }
            chart.draw(chartdata,chartoptions);
            // jQuery(titleDiv).append('<h5>' + tableTitle + '</h5>');
        } else {
            jQuery.each(chartReturn, function( index, value ) {

                var tableTitle = joinTitle;

                titleDiv = jQuery('#sdchart_table_div_' +index + '_title');
                var titleSuffix = index === 0 ? ': Numbers of people' : (index == 1 ? ': Percentages' : ': Dollars and Hours');
                jQuery(titleDiv).append('<h5>' + tableTitle + titleSuffix + '</h5>');
                if(!chartReturn[index]) {
                    jQuery('#chart_div_' + index).empty();
                    titleDiv.empty();
                    return false;
                }
            
                containerDiv = 'chart_div_' + index;
                container = document.getElementById(containerDiv);
                chartdata = new google.visualization.DataTable(chartReturn[index]);
                chart = new google.visualization.ColumnChart(container);
                chart.draw(chartdata,chartoptions);
            });

        }
    }
    else if(reportURL.get('report') == 'national') {

            
        var joinTitle = buildTableTitle('comparison');
        var natoptions = {
            region: 'US',
            displayMode: 'regions',
            resolution: 'provinces',
            width: '90%',
            height: '600',
          };
        titleDiv = jQuery('#sdchart_table_div_0_title');
        jQuery(titleDiv).append('<h5>' + joinTitle + '</h5>');
    
        var chartReturn = populatePrograms('chart'); 
        var containerDiv = 'chart_div_0';
        var container = document.getElementById(containerDiv);
        chartdata = new google.visualization.DataTable(chartReturn);
        var chart = new google.visualization.GeoChart(container);

            chart.draw(chartdata,natoptions);
    
           
        
    } else {
            var joinTitle = buildTableTitle('comparison');
            titleDiv = jQuery('#sdchart_table_div_0_title');
            jQuery(titleDiv).append('<h5>' + joinTitle + '</h5>');
            var chartReturn = populatePrograms('chart'); 
                containerDiv = 'chart_div_0';
                container = document.getElementById(containerDiv);
                chartdata = new google.visualization.DataTable(chartReturn);
                chart = new google.visualization.ColumnChart(container);
                chart.draw(chartdata,chartoptions);

    }

    
    /* var observer = new MutationObserver(function(mutations) {

        rectArray = ['rect[fill="#006e82"]','rect[fill="#fae6be"]','rect[fill="#00a0fa"]','rect[fill="#8214a0"]','rect[fill="#a0fa82"]','rect[fill="#fa7850"]','rect[fill="#005ac8"]','rect[fill="#f0f032"]', 'rect[fill="#0ab45a"]','rect[fill="#000000"]','rect[fill="#f5b7b1"]','rect[fill="#85929e"]'];
        jQuery.each(rectArray,function(i, value) {

            jQuery(value).each(function() {
                rectWid = jQuery(this).attr('width');
            rectHeight = jQuery(this).attr('height');
            jQuery(this).attr('stroke-dasharray', rectWid + ',' + rectHeight);
            //console.log('stroke-dasharray ' + rectWid +',' + rectHeight);
            });


        });

        jQuery('rect[fill="#006e82"]').attr('stroke', '#006e82');
        jQuery('rect[fill="#006e82"]').attr('stroke-width', '4');
        jQuery('rect[fill="#fae6be"]').attr('stroke', '#000000');
        jQuery('rect[fill="#fae6be"]').attr('stroke-width', '4');
        jQuery('rect[fill="#00a0fa"]').attr('stroke', '#00a0fa');
        jQuery('rect[fill="#00a0fa"]').attr('stroke-width', '4');
        jQuery('rect[fill="#8214a0"]').attr('stroke', '#cccccc');
        jQuery('rect[fill="#8214a0"]').attr('stroke-width', '4');
        jQuery('rect[fill="#a0fa82"]').attr('stroke', '#a0fa82');
        jQuery('rect[fill="#a0fa82"]').attr('stroke-width', '4');
        jQuery('rect[fill="#fa7850"]').attr('stroke', '#000000');
        jQuery('rect[fill="#fa7850"]').attr('stroke-width', '4');
        jQuery('rect[fill="#005ac8"]').attr('stroke', '#005ac8');
        jQuery('rect[fill="#005ac8"]').attr('stroke-width', '4');
        jQuery('rect[fill="#f0f032"]').attr('stroke', '#000000');
        jQuery('rect[fill="#f0f032"]').attr('stroke-width', '4');
        jQuery('rect[fill="#0ab45a"]').attr('stroke', '#0ab45a');
        jQuery('rect[fill="#0ab45a"]').attr('stroke-width', '4');
        jQuery('rect[fill="#000000"]').attr('stroke', '#000000');
        jQuery('rect[fill="#000000"]').attr('stroke-width', '4');
        jQuery('rect[fill="#f5b7b1"]').attr('stroke-width', '4');
        jQuery('rect[fill="#f5b7b1"]').attr('stroke', '#000000');
        jQuery('rect[fill="#85929e"]').attr('stroke-width', '4');
        jQuery('rect[fill="#85929e"]').attr('stroke', '#85929e');
    });
    observer.observe(container, {
        childList: true,
        subtree: true
    }); */
   
    //console.log(tableTitle);



       var dataReturn = [];
       if(!changeChart) {
            dataReturn = populatePrograms('table');
            var data = reportURL.get('report') == 'single' ? new google.visualization.DataTable(dataReturn[0]) : new google.visualization.DataTable(dataReturn);
            if (!jQuery('#downCSV').hasClass('visible')) jQuery('#downCSV').addClass('visible');

            var table = new google.visualization.Table(document.getElementById('sdchart_table_div_0'));
            var options = {
                        'showRowNumber': false,
                        'width':'95%',
                        'allowHtml': true,
                        'height':'100%'
                };
                table.draw(data, options);

            if(reportURL.get('report') == 'single') {

                if(!dataReturn[1]) {
                    jQuery('#sdchart_table_div_2').empty();
                    return;
                }
                var data = new google.visualization.DataTable(dataReturn[1]);
                if (!jQuery('#downCSV').hasClass('visible')) jQuery('#downCSV').addClass('visible');

                var table = new google.visualization.Table(document.getElementById('sdchart_table_div_1'));
                var options = {
                            'showRowNumber': false,
                            'width':'95%',
                            'allowHtml': true,
                            'height':'100%'
                 };
                 
                 var formatter = new google.visualization.NumberFormat({decimalSymbol: '.',groupingSymbol: ',', suffix: '%'});
                 formatter.format(data, 1);
                 formatter.format(data, 2);
                 


                table.draw(data, options);

                if(!dataReturn[2]) {
                    jQuery('#sdchart_table_div_2').empty();
                    return;
                }
                var data = new google.visualization.DataTable(dataReturn[2]);
                if (!jQuery('#downCSV').hasClass('visible')) jQuery('#downCSV').addClass('visible');

                var table = new google.visualization.Table(document.getElementById('sdchart_table_div_2'));
                var options = {
                            'showRowNumber': false,
                            'width':'95%',
                            'allowHtml': true,
                            'height':'100%'
                };


                table.draw(data, options); 
            }
       }


}


function doQuery(q, i, reportHeader, reportchoice) {

    var tableTarget = 'table_div_' + i;
    var tableTitleTarget = 'table_div_' + i + '_title';

    if (reportchoice == '30') {
        return;
    } else if (reportchoice >= '31' && reportchoice <= '36') {
        jQuery('#' + tableTitleTarget).append('<h5><strong>' + reportHeader + ' ' + yearstext + '</strong></h5><p class="tableInstruct"><em>Select the table headings to sort table data</em></p>');
        if (jQuery('#' + tableTarget).hasClass('collapse')) jQuery('#' + tableTarget).removeClass('collapse');
        jQuery('#' + tableTarget).attr('aria-labelledby', tableTitleTarget).attr('data-parent', '#summ_accordion').attr('aria-expanded', true);

    }
     else {
        jQuery('#' + tableTitleTarget).append('<h5><strong>' + reportHeader + ' in ' + statenametext + ' for ' + yearstext + '</strong></h5><p class="tableInstruct"><em>Select the table headings to sort table data</em></p>');
        if (jQuery('#' + tableTarget).hasClass('collapse')) jQuery('#' + tableTarget).removeClass('collapse');
        jQuery('#' + tableTarget).attr('aria-labelledby', tableTitleTarget).attr('data-parent', '#summ_accordion').attr('aria-expanded', true);

    }
    q.send(function(response) {
        var data = response.getDataTable();
        var dataView = new google.visualization.DataView(data);
        var numrows = dataView.getNumberOfRows();
        if (numrows === 0 && i === 0 && !clearAll) {
            jQuery('#chart_div_0').prepend('<h5>Your query produced no results.  Try again.</h5>');
            jQuery('#' + tableTitleTarget + ' h5').remove();
            jQuery('#chart_div_0 > div').remove();
            jQuery('#legend_div').empty();
            if(jQuery('button#spreadDL').hasClass('toggleShow')) jQuery('button#spreadDL').removeClass('toggleShow');
            if(jQuery('.switchNum').hasClass('toggleShow'))jQuery('.switchNum').removeClass('toggleShow');
            jQuery('.dlHeading').hide();
            if(jQuery('button#printButton').hasClass('toggleShow')) jQuery('button#printButton').removeClass('toggleShow');
            return;
        }
        data.setProperty(0, 0, 'style', 'width:100px');

        if (reportchoice != '30') {
            var container = document.getElementById(tableTarget);
            var table = new google.visualization.Table(
                container);
            table.draw(data, {
                showRowNumber: false,
                allowHtml: true
            });
        }



    });




}
jQuery(document).on('change','input:radio[name="vrcat"]', function() {

        if (jQuery(this).is(':checked')) {
          if(!jQuery('div#vrvars').hasClass('showVars')) jQuery('div#vrvars').addClass('showVars');
        }
});
jQuery(document).on('change','input:radio[name="acscat"]', function() {

        if (jQuery(this).is(':checked')) {
          if(!jQuery('div#acsvars').hasClass('showVars')) jQuery('div#acsvars').addClass('showVars');
        }
});
jQuery(document).on('click','#accordion h5 button', function() {
  var accordParent = jQuery(this).closest('.card');
  var parentID = accordParent.attr('id');
  jQuery('#' + parentID + ' input:radio[name="reportChoose"]').prop('checked', false);
  jQuery('#' + parentID + ' input:radio[name="vrcat"]').prop('checked', false);
  jQuery('#' + parentID + ' input:radio[name="acscat"]').prop('checked', false);
  jQuery('#' + parentID + ' input:checkbox[name="reportMultiChoose[]"]').prop('checked', false);
  if(jQuery('div#vrvars').hasClass('showVars')) jQuery('div#vrvars').removeClass('showVars');
  if(jQuery('div#acsvars').hasClass('showVars')) jQuery('div#acsvars').removeClass('showVars');
});
function updateSelectCount(checkType) {
    if (checkType == 'year') {
        var yeararray1 = getStateYearArray('year');
        //if (yeararray1.length < 1 && reportURL[1] == 'natrep') return;
        var years1 = reportURL.get('grp') == 'natrep' ? jQuery('input[name="summChoose"]:checked').val() : yeararray1;
        yearstext = reportURL.get('grp') != 'natrep' ? yeararray1.join(', ') : years1;
        console.log('Year Array:' + years1);
        jQuery('#yearCountText').empty();
        jQuery('#summyearCountText').empty();
        var yrsSelect = yearstext;

        yrsSelectText = yrsSelect;
        jQuery('#yearCountText').append(yrsSelectText);
        jQuery('#summyearCountText').append(yrsSelectText);

    }
    if (checkType == 'state') {
        var statearray1 = getStateYearArray('state');

        var statenametext = statearray1.length < 1 ? ' ' : statearray1.join(", ");
        // if (statearray1.length < 1) return;
        jQuery('#stateCountText').empty();
        var staSelect = statenametext;
        if (statenametext.length > 54) {
            staSelect = "Selected: (" + countChecks('state') + ") ";
        }
        staSelectText = staSelect;
        jQuery('#stateCountText').append(staSelectText);
    }

}
function getUrlString(reportVars) {
    var reportURL = decodeURIComponent(window.location.search.substring(1)),
        reportVariables = reportURL.split('&'),
        reportName,
        i;

    for (i = 0; i < reportVariables.length; i++) {
        reportName = reportVariables[i].split('=');

        if (reportName[0] === reportVars) {
            return reportName[1] === undefined ? true : reportName[1];
        }
    }
}


/* not doing anything
$(window).resize(function(){
  drawChart();
}); */
