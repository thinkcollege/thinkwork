/**
 * @file
 * Global utilities.
 *
 */
(function ($, Drupal) {



  'use strict';

  Drupal.behaviors.thinkwork8_boot = {
    attach: function (context, settings) {
      $(function () {
        $('[data-toggle="tooltip"]').tooltip()
      });

      $(function() {
        $(document).tooltip({ selector: '[data-toggle="tooltip"]' });
        $(document).popover({ selector: '[data-toggle="popover"]' });
      });
      $('button.navbar-toggler').click(function() {
        if (!$('#superfish-main-accordion').hasClass('sf-expanded')) {
          $('#superfish-main-toggle').click().hide();
        }
      });
      $('a.sf-depth-1',context).on('mouseover', function(event) {
        return false;
      });
      $('a.menuparent.sf-depth-1').on('click', function(event) {
        // Close any open dropdown menus.
       $.each($(this), function(index, element) {
          $(element).parent().parent().find('li.menuparent > ul').addClass('sf-hidden');
        });
        // Open the clicked dropdown menu.
        $(this).next().removeClass('sf-hidden');
        event.preventDefault();
      });

        $('a.sf-depth-2',context).on('mouseover', function(event) {
          return false;
        });
        $('a.menuparent.sf-depth-2').on('click', function(event) {
          // Close any open dropdown menus.
          $.each($(this), function(index, element) {
          $(element).parent().parent().find('li.menuparent > ul').addClass('sf-hidden');
        });
        // Open the clicked dropdown menu.
        $(this).next().removeClass('sf-hidden');
          event.preventDefault();
        });
        once('chartBuilder',"#chartBuilder").forEach(function(value,i) {
          var eventMethod = window.addEventListener ? "addEventListener" : "attachEvent";
          var eventer = window[eventMethod];
          var messageEvent = eventMethod == "attachEvent" ? "onmessage" : "message";

          eventer(messageEvent,function(e) {

            // If the message is a resize frame request
            if (e.data.indexOf('reheight::') != -1) {
              var height = e.data.replace('reheight::', '');

              document.getElementById('chartBuilder').style.height = height + 'px';
            }
            else if (e.data.indexOf('rewide::') != -1) {
              var width = e.data.replace('rewide::', '');
              document.getElementById('chartBuilder').style.width = width + 'px';
            }
          } ,false);
          $('input#bbState').on('click', function() {
            var stateSelect = $('#selectState option').filter(':selected').val();
            downloadState(stateSelect);

          });
      });
        function downloadState(d) {
          window.location =  '/sites/default/files/bbstates/' + d + '.pdf';
        }
        once('chartForm',"#sdChartForm").forEach(function(value,i) {
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
            columnSelected = $('input[name="reportChoose"]:checked').val();
            
            $(document).ready(function() {
            var urlLoc = window.location.href;  //get current url
                if(urlLoc.indexOf("?report=") == -1){ 
                document.location = urlLoc+"?report=single"; // redirect it
                } 
                var timeout;
                if (reportURL.get('showchart') && reportURL.get('showchart') != '' ) {
                    google.charts.load('current', { 'packages': ['corechart', 'table','line','geochart']});
                    timeout = setInterval(function () {
                        if (google.visualization != undefined) {
                            drawSDvisualization('storedChart');
                        clearInterval(timeout);
                        }
                    }, 300);
                
                
                } else
                google.charts.load('current', { 'packages': ['corechart', 'table','line','geochart']});
                if($('.introText').hasClass('hideIntro')) $('.introText').removeClass('hideIntro');
            })

            
            $(document).on('click','.google-visualization-table-table tbody tr', function() {
                if(reportURL.get('report') == 'single') {
                    $(".google-visualization-table-table tbody tr").each(function(i, obj) {
                        if($('.google-visualization-table-table tbody tr').hasClass('chartSelected')) $('.google-visualization-table-table tbody tr').removeClass('chartSelected');
                        
                    });
                    if($('.fa-chart-bar').length) $('.fa-chart-bar').remove();
                    $(this).addClass('chartSelected');
                    $('td:first',this).prepend('<i class="fas fa-chart-bar"></i>');

                }

            });
            
            $(document).on('click','.michRedraw', function() {
                if(!$('.introText').hasClass('hideIntro')) $('.introText').addClass('hideIntro');
                $('#sdchart_table_div_0').empty();
                $('#chart_div_0').empty();
                
                var searchWarnings = '';
            
                
                var contentCount;
                if(reportURL.get('report') == 'comparison' || reportURL.get('report') == 'national') 
                {  
                    $('p.privWarn').each(function(i, obj) {
                    if(!$(this).hasClass('conditional') && !$(this).hasClass('visible') ) $(this).addClass('visible');
                    });
                    contentCount = countChecks('variables');
                } else
                { 
                    contentCount = countChecks('table');


                    $('p.privWarn').each(function(i, obj) {
                        if(!$(this).hasClass('visible') ) $(this).addClass('visible');
                        });
                /* var timeout;
                    timeout = setInterval(function () {
                        $(".google-visualization-table-table").each(function(i, obj) {
                            $('tbody tr:first', this).addClass('chartSelected');
                            
                        });
                    }, 300); */
                }
            
                
            
                if (countChecks('states') > 0 && countChecks('years') > 0 && contentCount > 0 ){
                
                    $('#tableWarnings').empty();
                    var newChart = drawSDvisualization();
                    $('html, body').animate({
                        scrollTop: $("h1.title").offset().top
                    }, 500);

                    if(!$('p.privWarn').hasClass('visible')) $('p.privWarn').addClass('visible');
                }
                else {
                    if ($('#downCSV').hasClass('visible')) $('#downCSV').removeClass('visible');
            
                    searchWarnings += !contentCount > 0 ? 'Check some data sources for your search.' : '';
                    searchWarnings += !countChecks('states') > 0 ? '<br />Check some states for your search.' : '';
                    searchWarnings += countChecks('years') < 1 ? '<br />Check some years at left for your search.' : '';
                    $('#chart_div_0').append('<p class="searchWarnings">' + searchWarnings + '</p>');
                    if($('p.privWarn').hasClass('visible')) $('p.privWarn').removeClass('visible'); 
                }
            
                
            });
            
            $(document).on('click','.tableSelect', function() {
            var tableIDval = "";
            
            
                dataTable = $(this).val();
                var valParentId = $(this).closest('.collapse').attr('id');
                $('#chosenText').remove();
                $('img#chosenCheck').remove();
                $('.tableSelect').each(function(i, obj) {
                $(this).removeClass('tableChosen');
                if($(this).val() != dataTable) $(this).prop('checked', false);
                if(!$(this).closest('.collapse').is("#" + valParentId)) $(this).closest('.collapse').removeClass('show');
                
            
            
                });
            
                
                $(this).addClass('tableChosen');
                if(reportURL.get('report') == 'single')
            { 
                if($(this).is('input')) {
                    var varname = $(this).closest('label').text();
                    updateSelectCount('table',varname);
                }
                var tableIndicator = '&nbsp;<img id="chosenCheck" src= "/themes/custom/thinkwork8_boot/img/green_check.png" alt="this table chosen" />';
                $(this).parent('label.js-simple-tooltip').length ? $(this).closest('label.js-simple-tooltip').append(tableIndicator) : $('.tableChosen').after(tableIndicator);
            }
                console.log("Table: " + dataTable);
                var tableDescrip = $(this).attr("title") ? $(this).attr("title") : $(this).closest('label').attr("title");
                $('input#tableId').val(dataTable);
                $('input#tableDescrip').val(tableDescrip);
            
            });
            
            
            
            $(document).on('click','input[name="reportChoose"]', function() {
            var tableIDval = "";
            var tableDescrip = "";
            $('#chosenText').remove();
            if($(this).hasClass('acsVars')) {
                tableIDval = $('input[name="acscat"]:checked').val();
                var tableDescrip = $('input[name="acscat"]:checked').parent('label').attr("title");
            
            } else if ($(this).hasClass('vrVars')) {
            tableIDval = $('input[name="vrcat"]:checked').val();
            var tableDescrip = $('input[name="vrcat"]:checked').parent('label').attr("title");
            } else {
                tableIDval = $(this).closest('div.altSelect').attr('id');
                var tableDescrip = $(this).closest('.card').find('h5 button').attr("title");
            
            }
            if(reportURL.get('report') == 'comparison' || reportURL.get('report') == 'national') {
                $('#chosenCheck').remove();
                $('input[name="reportChoose"]').each(function(i, obj) {
                    $(this).removeClass('variableChosen');
                });
                $(this).addClass('variableChosen');
            
                var varname = $(this).closest('label').text();
                var varParent;
                if($(this).closest('.card').find('input.tableChosen').length) { 
                    varParent = $(this).closest('.card').find('input.tableChosen').parent('label').text();
                    varParent = varParent + ': ';
                }
                else varParent = '';
                varname = varParent + varname;
                updateSelectCount('variable',varname);
                var variableIndicator = '&nbsp;<img id="chosenCheck" src= "/themes/custom/thinkwork8_boot/img/green_check.png" alt="this variable chosen" />';
                $(this).parent('label.js-simple-tooltip').length ? $(this).closest('label.js-simple-tooltip').append(variableIndicator) : $('.variableChosen').after(variableIndicator);
            }
            $('input#tableId').val(tableIDval);
            $('input#tableDescrip').val(tableDescrip);
            console.log("Table description: " + tableDescrip);
            });
            
            
            
            
            
            $(document).on('change','select#rptPeriodContainer', function() {
                var getProgram = populatePrograms('programcull');
                });
            $(document).on('change','input[name="regionTrendPer[]"]', function() {
                trendInfo = checkTrends();
            
            });
            $(document).on("change",'input:checkbox[name="regionPayer[]"]', function () {
            
            //console.log(payerarray1);
            //$('#programContainer').empty();
                var getProgram = populatePrograms('programcull');
            
            
            });
            $(document).on('click','#sdChartForm input.checkAll',function() {
                var parentID = $(this).closest('div.formSub').attr('id');
                //console.log(parentID);
            
                var checked = $(this).prop('checked');
                $('#' + parentID).find('ul input:checkbox').prop('checked', checked);
            // $('#programContainer').empty();
            // var getProgram = populatePrograms('program');
            });
            
            
            // All the document ready stuff
            $(document).ready(function() {
                var singleActivRadio = singleActiveFilter();
                var checktrends = checkTrends();
                $("#sdChartForm button.michRedraw").click(function(event) {
                event.preventDefault(); // cancel default behavior
            
            
                });
                var grpNameval = getUrlString('grp');
                $('input#grpName').val(grpNameval);
            
                updateSelectCount('state');
                updateSelectCount('year');
            
                $('#collapseSeven input').click(function(event) {
                    updateSelectCount('state');
            
                });
                $('#collapseEight input').click(function(event) {
                    updateSelectCount('year');
            
                });
                $('#collapseNine input').click(function(event) {
                    updateSelectCount('year');
            
                });
            
                $("#sdChartForm button.chooseControl").click(function(event) {
                    event.preventDefault(); // cancel default behavior
            
            
                });
            
            
                $("#sdChartForm button#clearForm").click(function(event) {
                    event.preventDefault(); // cancel default behavior
            
            
                });
            
            
                var programSlug = reportURL.get('grp') == 'natrep' ? 'Programs' : 'CMHSPs';
                console.log('Group from URL: ' + reportURL.get('grp'));
                $('span.programSlug').text(programSlug);
                var pageSub = reportURL.get('grp') == 'indst' ? 'Individual State Report' : (reportURL.get('grp') == 'stcomp' ? 'State Comparison Report' : (reportURL.get('grp') == 'natrep' ? 'National Report' : ''));
                if ($('#downCSV').hasClass('visible')) $('#downCSV').removeClass('visible');
                if($('p.privWarn').hasClass('visible')) $('p.privWarn').removeClass('visible');
                $('a#singleTab').attr('href','/statedata/chart-builder-test?report=single');
                $('a#comparisonTab').attr('href','/statedata/chart-builder-test?report=comparison');
                $('a#nationalTab').attr('href','/statedata/chart-builder-test?report=national');
                if($('#buttonRow a').hasClass('active')) $('#buttonRow a').removeClass('active');
                $('#buttonRow a.btn-' + reportURL.get('grp')).addClass('active');
                $('#buttonRow a.btn-indst').attr('href','/statedata/chart-builder-test?report=' + reportURL.get('report') + '&grp=indst' );
                $('#buttonRow a.btn-stcomp').attr('href','/statedata/chart-builder-test?report=' + reportURL.get('report') + '&grp=stcomp' );
                $('#buttonRow a.btn-natrep').attr('href','.//statedata/chart-builder-test?report=' + reportURL.get('report') + '&grp=natrep' );
                $('#downCSV').on('click',function() {
                    downloadCSV();
            
                });
            
            
                $('#trendActivityContainer input[name="trendActivityCat"]').on('change',function() {
                    var singleActiv = $('#trendActivityContainer input[name="trendActivityCat"]:checked').val();
                    singleActiveFilter();
            
                });
                $('#sdChartForm input.checkAll').prop('checked',false);
                $('#sdChartForm input.progToggle').prop('checked',false);
                //$('#programHed').hide();
                //var payerarray = populatePayers(reportURL.get('grp'));
            
                var getProgram = populatePrograms('program');
                var getSingle = reportURL.get('report') == 'single' ? populateSingle() : null;
            
                if(reportURL.get('report') == 'single') {
            
                    $('.chartsection .altSelect').removeClass('showVis');
                    $('button.tableSelect').removeAttr('data-target');
                    $('.chartsection .altSelect.singleSelect').removeClass('showVis');
                    $('#collapseEight .altSelect.singleSelect').removeClass('showVis');
                    $('#collapseEight .altSelect.multiSelect').addClass('showVis');
                    $('#collapseSeven .altSelect.multiSelect').removeClass('showVis');
                    $('#collapseSeven .altSelect.singleSelect').addClass('showVis');
                    $('.stateSection').removeClass('natRemove');
            
                } else if (reportURL.get('report') == 'comparison') {
                    $('.chartsection .altSelect').removeClass('showVis');
                    $('.chartsection .altSelect.multiSelect').addClass('showVis');
                    $('#collapseSeven .altSelect.multiSelect').addClass('showVis');
                    $('#collapseSeven .altSelect.singleSelect').removeClass('showVis');
                    $('#collapseOne .altSelect.singleSelect').addClass('showVis');
                    $('#collapseTwo .altSelect.singleSelect').addClass('showVis');
                    $('#collapseThree .altSelect.singleSelect').addClass('showVis');
                    $('#collapseFour .altSelect.singleSelect').addClass('showVis');
                    $('#collapseFive .altSelect.singleSelect').addClass('showVis');
                    $('#collapseSix .altSelect.singleSelect').addClass('showVis');
            
            
                } else if(reportURL.get('report') == 'national') {
                    $('.chartsection .altSelect').removeClass('showVis');
                    $('.chartsection .altSelect.singleSelect').addClass('showVis');
                    $('#collapseEight .altSelect.multiSelect').removeClass('showVis');
                    $('#collapseEight .altSelect.singleSelect').addClass('showVis');
                    $('.chartsection.stateSection').addClass('natRemove');
            
            
                }
                collapseEight
                if(reportURL.get('report') == 'comparison') {
                    if($('main .formSub').hasClass('selectShow')) $('main .formSub').removeClass('selectShow');
                    if($('.tabSelect').hasClass('selected')) $('.tabSelect').removeClass('selected');
                    if(!$('#comparisonTab').hasClass('selected')) $('#comparisonTab').addClass('selected');
                    if(!$('#comparisonDiv').hasClass('selectShow')) $('#comparisonDiv').addClass('selectShow');
                    if(!$('#rptPeriodDiv').hasClass('active')) $('#rptPeriodDiv').addClass('active');
                } else if(reportURL.get('report') == 'single') {
                    if($('main .formSub').hasClass('selectShow')) $('main .formSub').removeClass('selectShow');
                    if($('.tabSelect').hasClass('selected')) $('.tabSelect').removeClass('selected');
                    if(!$('#singleTab').hasClass('selected')) $('#singleTab').addClass('selected');
                    if(!$('#singleDiv').hasClass('selectShow')) $('#singleDiv').addClass('selectShow');
                    if(!$('#rptPeriodDiv').hasClass('active')) $('#rptPeriodDiv').addClass('active');
                } else if(reportURL.get('report') == 'national') {
                    if($('main .formSub').hasClass('selectShow')) $('main .formSub').removeClass('selectShow');
                    if($('.tabSelect').hasClass('selected')) $('.tabSelect').removeClass('selected');
                    if(!$('#nationalTab').hasClass('selected')) $('#nationalTab').addClass('selected');
                    if(!$('#nationalDiv').hasClass('selectShow')) $('#nationalDiv').addClass('selectShow');
                    if($('#rptPeriodDiv').hasClass('active')) $('#rptPeriodDiv').removeClass('active');
                }
                $('#programHed input:checkbox').on('change',function() {
                    if($(this).prop('checked')) {
                            if ($('#programContainer').hasClass('hideMe')) {$('#programContainer').removeClass('hideMe');
                            }
                            if(reportURL.get('report') == 'national' && $('input[name="regionTrendPer[]"]:checked').length < 2) {
                                if (!$('#noDates').length) $('#programHed').after('<p class="searchWarnings sWsmaller clearable" id="noDates">Select at least two reporting periods at top right to begin searching by program</p>');
                                $('input.progToggle').prop('checked', false);
            
                            } else {
                                if ($('#noDates').length) {
                                    $('#noDates').remove();
                                }
                                populatePrograms('programcull');
                            }
                        }
                        else {
                                if (!$('#programContainer').hasClass('hideMe')) {
                                    $('#programContainer').addClass('hideMe') ;
                                    $('#programContainer').find('input:checkbox').prop('checked', false);
                                }
                            }
                    });
            
            
            
            
            
            
            
            
            
            
                // var updateSelections = readSelections();
                var checkState = countChecks('state');
                var checkYears = countChecks('year');
                var checkCats = countChecks('category');
                var checkPrograms = countChecks('program');
                var checkPayers = countChecks('payer');
            
                $("#chartRedraw").click(function() {
                    $('html, body').animate({
                        scrollTop: $("h1.title").offset().top
                    }, 500);
                });
            
                //if(useRawNum == true) { if(!$('.percOn').hasClass('toggleHide')) $('.percOn').addClass('toggleHide'); if($('.numOn').hasClass('toggleHide')) $('.numOn').removeClass('toggleHide');} else { if(!$('.numOn').hasClass('toggleHide')) $('.numOn').addClass('toggleHide'); if($('.percOn').hasClass('toggleHide')) $('.percOn').removeClass('toggleHide');}
            
                $('.switchNum button').on('click', function() {
                    useRawNum =  useRawNum == true ? false : true;
                    //console.log(useRawNum);
                    if(useRawNum == true) {
                        if(!$('.percOn').hasClass('toggleHide')) $('.percOn').addClass('toggleHide'); if($('.numOn').hasClass('toggleHide')) $('.numOn').removeClass('toggleHide'); $('.numOn').addClass('toggleShow');
                    } else
                    {
                        if(!$('.numOn').hasClass('toggleHide')) $('.numOn').addClass('toggleHide'); if($('.percOn').hasClass('toggleHide')) $('.percOn').removeClass('toggleHide'); $('.percOn').addClass('toggleShow');
                    }
            
                });
            
                $('input[name="dlChoose"]').on('click', function() {
                    var dlType = $('input[name="dlChoose"]:checked').val();
                    useRawNum = dlType == 'Rawnumber' ? true : false;
                    //var redraw = drawSheetName();
            
                });
            
                $("#clearForm").click(function() {
                    $('#sdChartForm input').each(function(i, obj) {
                        $(this).prop('checked', false);
                    });
                    $('.clearable').each(function(i, obj) {
                        $(this).empty();
                    });
                    $('.collapse').each(function(i, obj) {
                        $(this).removeClass('show');
                    });
                    $('#chosenText').remove();
                    $('#chosenCheck').remove();
                    $('#chart_div_0').empty();
                    $('#legend_div').empty();
                    $('#yearCountText').empty();
                    $('#stateCountText').empty();
                    $('#sdchart_table_div_0').empty();
                    $('#nationalTitle').empty();
                    $('#sdchart_table_div_0_title').empty();
                    $('#sdchart_table_div_1_title').empty();
                    $('#sdchart_table_div_2_title').empty();
                    $('#downCSV').removeClass('visible');
                    $('.privWarn').removeClass('visible');
                    if($('.introText').hasClass('hideIntro')) $('.introText').removeClass('hideIntro');
            
            
                    clearAll = true;
            
            
                    $('html, body').animate({
                        scrollTop: $("#chartTitle").offset().top
                    }, 500);
                });
            
                $('input.checkAll').click(function() {
                    var parentID = $(this).closest('div.collapse').attr('id');
                    // console.log(parentID);
            
                    var checked = $(this).prop('checked');
                    $('#' + parentID).find('.col input:checkbox').prop('checked', checked);
                });
            
                $('select').change(countChecks);
                //$('#accordion label').accessibleSimpleTooltipAria({
                //   simpletooltipText: 'title'
                //});
                $("#chartSelector").submit(function(event) {
                //   event.preventDefault(); // cancel default behavior
            
            
                });
                //updateSelectCount('state');
            // updateSelectCount('year');
            
            
            
                $('#printButton').click(function() {
                window.print();
                });
            
            
            
            
            
                $('#accordion .chartselection input').click(function() {
                    $("input[type='radio']:checked").each(function() {
                        var catIdValue = $(this).attr("id");
                        updateCatNameText(catIdValue);
                    });
                });
            
                // if (checkState != 0 && checkYears != 0 && checkCats != 0) google.charts.setOnLoadCallback(drawSheetName);
                var reportType = getUrlString('report');
                // end of document.ready functions
            
            });
            function checkTrends() {
            var countDates;
                countDates = countChecks('trendPers');
                console.log(countDates);
                if(countDates < 2) {
                    if($('#nationalActivityDiv').hasClass('active')) $('#nationalActivityDiv').removeClass('active');
                    if($('#singleActivity').hasClass('active')) $('#singleActivity').removeClass('active')
                    }
                else if (countDates >= 2) {
                    if(!$('#nationalActivityDiv').hasClass('active')) $('#nationalActivityDiv').addClass('active');
                    if(!$('#singleActivity').hasClass('active') && $('#nationalActivityContainer input[name="nationalActivityCat"]:checked').val()) $('#singleActivity').addClass('active');
                    }
            
            }
            
            function singleActiveFilter() {
                var singleActiv = $('#nationalActivityContainer input[name="nationalActivityCat"]:checked').val();
                if(!$('#singleActivity').hasClass('active') && singleActiv) $('#singleActivity').addClass('active');
                if($('#nationalActivitySelContainer li').hasClass('active')) $('#nationalActivitySelContainer li').removeClass('active');
                if(singleActiv == '1' || singleActiv == '2' || singleActiv == '4') {
                    if(!$('#nationalActivitySelContainer li').hasClass('active')) $('#nationalActivitySelContainer li').addClass('active');
                }
                else if(singleActiv == '3' || singleActiv == '98' ) {
                    if($('#nationalActivitySelContainer li.limited').hasClass('active')) $('#nationalActivitySelContainer li.limited').removeClass('active');
                    if(!$('#nationalActivitySelContainer li.unlimited').hasClass('active')) $('#nationalActivitySelContainer li.unlimited').addClass('active');
                }
            }
            
            
            function getCheckboxLabels(type) {
            
            
                
                var choiceNames = '';
                if (type == 'comparison' || type == 'national') {
                    var progId = $('input[name="reportChoose"]:checked').attr("id");
                    var progText = $("label[for='"+progId+"']").text();
                    var progPrefix = $('input[name="reportChoose"]:checked').closest('.card').find('.tableChosen').attr("title") ? $('input[name="reportChoose"]:checked').closest('.card').find('.tableChosen').attr("title") : $('input[name="reportChoose"]:checked').closest('.card').find('.tableChosen').closest('label').attr("title");
                    choiceNames = progPrefix + ": " + progText;
                    console.log("Prog prefix: " + progPrefix + ', Prog text: ' + progText);
                }
                else if (type == 'single') {
                    choiceNames = [];
                    $('.tableChosen').each(function() {
                        var tableDescrip = $(this).attr("title") ? $(this).attr("title") : $(this).closest('label').attr("title");
                    
                        choiceNames.push(tableDescrip);
            
                    });
                }
                console.log('Table descripff: ' + choiceNames);
                return choiceNames;
            
            }
            function getAgeLabel() {
                agestart = $('#ags option:selected').val();
                ageend = $('#age option:selected').val();
            
            
                var ageText = parseInt(agestart) === 0 && parseInt(ageend) === 108 ? 'Ages: all' : 'Ages: ' + agestart + ' to ' + ageend
            
                return ageText;
            
            }
            function getRptPerLabel() {
                var rptPerLabel = $('#rptPeriodContainer').find(":selected").text();
                return rptPerLabel;
            
            }
            function getGenderLabel() {
                gender = $('#genderSelect option:selected').text();
            
            
                var genderText = 'Gender: ' + gender;
            
                return genderText;
            
            }
            
            function populateSingle() {
                if(!$('#singleTab').hasClass('selected')) $('#singleTab').addClass('selected');
            
                if(!$('#singleDiv').hasClass('selectShow')) $('#singleDiv').addClass('selectShow');
                $.ajax({
                    url: '/themes/custom/thinkwork8_boot/chartbuilder/sd_chartcall.php?type=nonPop',
                    type: "POST",
                    dataType: "json",
                    cache: false,
                    success: function(data) {
                        //console.log(data);
                        $.each(data, function () {
                            var options = "";
                            options += '<li><label>' + '<input type="checkbox" value="' + this.field_choice_code + '" name="regionSingle[]" /><span class="textLabel">' + this.field_choice_label + '</span></label></li>';
                            $("#singleContainer").append(options
                                )
                        })
            
            
            
                    },
                    error: function(xhr, status, err) {
                    console.log('error')
                    }
            
                });
            
            
            
            }
            

            function populatePrograms(slug,tableindex, numpercdol ) {
                var urlid = null;
                var storedID = '';
                if(reportURL.get('showchart') && reportURL.get('showchart') != '') {
                    urlid = reportURL.get('showchart');
            
                    storedID = '&urlid=' + urlid;
                }
            
            
                if (slug == 'program') { $.ajax({
                        url: '/themes/custom/thinkwork8_boot/chartbuilder/sd_chartcall.php?type=' + slug,
                        type: "POST",
                    //data: $('#sdChartForm').serialize(),
                        dataType: "json",
                        cache: false,
                        success: function(data) {
            
                        $.each(data, function () {
                                var $options = "";
                                $options += "<li><label>" + '<input type="checkbox" value="' +  + this.program + '" name="regionProgram[]"><span class="textLabel">' + this.field_choice_label + ' (' + this.countinds + ')</span></label></li>';
                                    $("#programContainer")
                                    .append($options
                                    );
            
                            })
                        // if (!$.trim(data)){ $('#programHed').hide(); console.log('fuggabe');} else { $('#programHed').show();}
            
            
            
            
                        },
                        error: function(xhr, status, err) {
                        console.log('error')
                        }
                    });
                }
                else if (slug == 'programcull') {
                    var viewSlug = reportURL.get('report') ? reportURL.get('report') : 'comparison';
                    var grpSlug = reportURL.get('grp') ? reportURL.get('grp') : 'indst';
                    $("#programContainer").empty();
                    $.ajax({
                    url: '/themes/custom/thinkwork8_boot/chartbuilder/sd_chartcall.php?type=' + slug + '&grp=' + grpSlug + '&view=' + viewSlug,
                    type: "POST",
                    data: $('#sdChartForm').serialize(),
                    dataType: "json",
                    cache: false,
                    success: function(data) {
            
                    $.each(data, function () {
            
                            var $options = "";
                            $options += "<li><label>" + '<input type="checkbox" value="' +  + this.program + '" name="regionProgram[]"><span class="textLabel">' + this.field_choice_label + ' (' + this.countinds + ')</span></label></li>';
                                $("#programContainer")
                                .append($options
                                )
            
                        })
            
            
            
            
                    },
                    error: function(xhr, status, err) {
                    console.log('error')
                    }
                });
                }
            
                else if(slug == 'table' || slug == 'storetable') {
                    // if it's showchart get the url get values from the url_store not the url
                
                    var reportType = reportURL.get('showchart') ? getReportVar('report') : (reportURL.get('report') ? reportURL.get('report') : 'comparison');
                    
                    if(reportType == 'single') {
                        var returnedStuff1 =
                        $.ajax({
                            url: '/themes/custom/thinkwork8_boot/chartbuilder/sd_chartcall.php?reportType=' + reportType + '&type=table&singletype=num' + storedID,
                            beforeSend: function(){
                                $("#sdchart_table_div_0").html('<img class="loadingImg" src="/themes/custom/thinkwork8_boot/img/spinner.gif">');
                            },
                            type: "POST",
                            data: $('#sdChartForm').serialize(),
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
                        $.ajax({
                            url: '/themes/custom/thinkwork8_boot/chartbuilder/sd_chartcall.php?reportType=' + reportType + '&type=table&singletype=perc' + storedID,
                            beforeSend: function(){
                                $("#sdchart_table_div_1").html('<img class="loadingImg" src="/themes/custom/thinkwork8_boot/img/spinner.gif">');
                            },
                            type: "POST",
                            data: $('#sdChartForm').serialize(),
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
                        $.ajax({
                            url: '/themes/custom/thinkwork8_boot/chartbuilder/sd_chartcall.php?reportType=' + reportType + '&type=table&singletype=dol' + storedID,
                            beforeSend: function(){
                                $("#sdchart_table_div_2").html('<img class="loadingImg" src="/themes/custom/thinkwork8_boot/img/spinner.gif">');
                            },
                            type: "POST",
                            data: $('#sdChartForm').serialize(),
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
                        console.log("Ajax table URL: " + '/themes/custom/thinkwork8_boot/chartbuilder/sd_chartcall.php?reportType=' + reportType + '&type=table' + storedID);
            
                        var returnedStuff =
                        $.ajax({
                            url: '/themes/custom/thinkwork8_boot/chartbuilder/sd_chartcall.php?reportType=' + reportType + '&type=table' + storedID,
                            beforeSend: function(){
                                $("#sdchart_table_div_0").html('<img class="loadingImg" src="/themes/custom/thinkwork8_boot/img/spinner.gif">');
                            },
                            type: "POST",
                            data: $('#sdChartForm').serialize(),
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
            
                    console.log( 'in function table: ' + returnedStuff);
                    return returnedStuff;
            
                } 
            
                else if(slug == 'chart' || slug == 'storechart') {
                    
                    
                    var reportType = reportURL.get('showchart') ? getReportVar('report') : (reportURL.get('report') ? reportURL.get('report') : 'comparison');
                    console.log('report type: ' + reportType);
                    
                    var changeChart = tableindex === 0 || tableindex ? '&tableindex=' + tableindex + '&numpercdol=' + numpercdol : '';
                    
                    if(reportType == 'single') {
                        
                        if((tableindex ===0 || tableindex) && numpercdol) {
                            var chartDivIndex = numpercdol == 'num' ? '#chart_div_0' :(numpercdol == 'perc' ? '#chart_div_1' : '#chart_div_2');
            
                            var returnedStuff1 =
                            $.ajax({
                                url: '/themes/custom/thinkwork8_boot/chartbuilder/sd_chartcall.php?reportType=' + reportType + '&type=chart&singletype=' + numpercdol + changeChart + storedID,
                                beforeSend: function(){
                                    $(chartDivIndex).html('<img class="loadingImg" src="/themes/custom/thinkwork8_boot/img/spinner.gif">');
                                },
                                type: "POST",
                                data: $('#sdChartForm').serialize(),
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
                            $.ajax({
                                url: '/themes/custom/thinkwork8_boot/chartbuilder/sd_chartcall.php?reportType=' + reportType + '&type=chart&singletype=num' + changeChart + storedID,
                                beforeSend: function(){
                                    $("#chart_div_0").html('<img class="loadingImg" src="/themes/custom/thinkwork8_boot/img/spinner.gif">');
                                },
                                type: "POST",
                                data: $('#sdChartForm').serialize(),
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
                            $.ajax({
                                url: '/themes/custom/thinkwork8_boot/chartbuilder/sd_chartcall.php?reportType=' + reportType + '&type=chart&singletype=perc' + storedID,
                                beforeSend: function(){
                                    $("#chart_div_1").html('<img class="loadingImg" src="/themes/custom/thinkwork8_boot/img/spinner.gif">');
                                },
                                type: "POST",
                                data: $('#sdChartForm').serialize(),
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
                            $.ajax({
                                url: '/themes/custom/thinkwork8_boot/chartbuilder/sd_chartcall.php?reportType=' + reportType + '&type=chart&singletype=dol' + storedID,
                                beforeSend: function(){
                                    $("#chart_div_2").html('<img class="loadingImg" src="/themes/custom/thinkwork8_boot/img/spinner.gif">');
                                },
                                type: "POST",
                                data: $('#sdChartForm').serialize(),
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
            
                        console.log( 'in function chart: ' + returnedStuff);
                        var returnedStuff = tableindex && numpercdol ? [returnedStuff1] : [returnedStuff1,returnedStuff2,returnedStuff3];
                    } else {
                        console.log("Ajax Chart URL: " + '/themes/custom/thinkwork8_boot/chartbuilder/sd_chartcall.php?reportType=' + reportType + '&type=chart' + storedID);
                        
            
                            var returnedStuff =
                            $.ajax({
                                url: '/themes/custom/thinkwork8_boot/chartbuilder/sd_chartcall.php?reportType=' + reportType + '&type=chart&' + storedID,
                                beforeSend: function(){
                                    $('#chart_div_0').html('<img class="loadingImg" src="/themes/custom/thinkwork8_boot/img/spinner.gif">');
                                },
                                type: "POST",
                                data: $('#sdChartForm').serialize(),
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
                    console.log( 'in function chart: ' + returnedStuff);
                    return returnedStuff;
            
                } 
            
            
            
            }
            function downloadCSV() {
                
                //var totalPrograms =countChecks('program');
                //var totalComparison =countChecks('comparison');
                var reportType = reportURL.get('report') ? '&reportType=' + reportURL.get('report') : '&reporttype=comparison';
                var singleType = reportURL.get('report') == 'single' ? '&singletype=num' : '';
            // var catType = totalPayers > 0 ? (totalPrograms > 0 ? '&category=catmixed':'&category=catpay'): (totalPrograms > 0 ? '&category=catprog' : '');
                var url = '/themes/custom/thinkwork8_boot/chartbuilder/sd_chartcall.php?type=download' + reportType +singleType;
                $('#sdChartForm').attr('action', url);
                var HiddenTtl = buildTableTitle('download');
                console.log(HiddenTtl);
                $('#titleInputs').append('<input type="hidden" name="sendTitle" value="' + HiddenTtl + '" />');
                var form = $(this);
                $.ajax({
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
                $.urlParam = function (name) {
                    var results = new RegExp('[\?&]' + name + '=([^&#]*)')
                                    .exec(window.location.search);
            
                    return (results !== null) ? results[1] || 0 : false;
                }
            
                    var returnVar1 = $.urlParam('report') ? $.urlParam('report') : 'comparison';
                    var returnVar2 = $.urlParam('grp') ? $.urlParam('grp') : 'indst';
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
                var countStates = reportURL.get('report') == 'comparison' ? $('input[name="regionStates[]"]:checked').length : ($('input[name="regionStatesInd"]').is(':checked') || reportURL.get('report') == 'national' ? 1 : 0) ;
                var countYears = reportURL.get('report') == 'national' ? $('input[name="summChoose"]:checked').length : $('input[name="regionYear[]"]:checked').length;
                var countTrendPers = $('input[name="regionTrendPer[]"]:checked').length;
                var checkCount = countStates * countYears;
                var varCount = $('input[name="reportChoose"]:checked').val() ? 1 : 0;
                var tableCount = $('.tableChosen').length ? 1: 0;
                var trendSel = $('#trendActivitySelContainer input[name="trendActivitySel"]:checked').val();
                var showStatewide = $('input[name="showStatewide[]"]:checked').length;
                var returnCount;
                switch (typeCount) {
                    case 'states':
                        returnCount = countStates;
                        break;
                        case 'years':
                            returnCount = countYears;
                            break;
            
                    case 'variables':
                        returnCount = varCount;
                        break;
                    case 'table':
                        returnCount = tableCount;
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
            /* function updateSelectCount(checkType) {
                if (checkType == 'year') {
                    yeararray1 = getStateYearArray('year');
            
                    $('#yearCountText').empty();
                    $('#summyearCountText').empty();
                    var yrsSelect = yearstext;
                    if (yearstext.length > 20) {
                        yrsSelect = "Selected: (" + countChecks('year') + ") ";
                    }
                    yrsSelectText = yrsSelect;
                    $('#yearCountText').append(yrsSelectText);
                    $('#summyearCountText').append(yrsSelectText);
            
                }
            
            } */
            
            function updateCatNameText() {
                $('#catChecked').empty();
            
            
            }
            
            function getStateYearArray(choiceType,reportType) {
            
                var statearray1 = [];
                var yeararray1 = [];
                if(reportType && reportType == 'comparison') {
                    var statename1 = $('input:checkbox[name="regionStates[]"]:checked').each(function() {
                    var stateName = $(this).closest('label').text();
                    statearray1.push(stateName);
                    });
                } else 
                {
                    var statename2= $('input:radio[name="regionStatesInd"]:checked').each(function() {
                        var stateName = $(this).closest('label').text();
                        statearray1.push(stateName);
                    });
                }
                var years1 = reportType == 'national' ? $('input[name="summChoose"]:checked').each(function() {
                    yeararray1.push($(this).val());
                }) : $('input:checkbox[name="regionYear[]"]:checked').each(function() {
                    yeararray1.push($(this).val());
                });
            
            
                return choiceType == 'year' ? yeararray1 : statearray1;
            
            }
            
            
            function getTableVarChosen(choiceType) {
            
                
                var varname = $('input.tableSelect]:checked').closest('label').text();
                    var tableName = $('input:checkbox[name="reportChoose"]:checked').closest('label').text();
                    
            
                
                var years1 = reportURL.get('grp') == 'natrep' ? $('input[name="summChoose"]:checked').each(function() {
                    yeararray1.push($(this).val());
                }) : $('input:checkbox[name="regionYear[]"]:checked').each(function() {
                    yeararray1.push($(this).val());
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
                var i;
            
                for (i = 0; i < numCount; i++) {
                    legendOut.push('<div class="legendElem"><div class="legendColorBlock legendNum' + i + '"><img src="/themes/custom/thinkwork8_boot/img/legend' + i + '.png" /></div><div class="legendText">' + legendIn[i] + '</div></div>');
            
                }
                return legendOut;
            }
            function buildTableTitle(type) {
                var breaker = type == 'download' ? ' | ' : ' | ';
                var reportType = reportURL.get('report');
                var tableTitle = getCheckboxLabels(reportType);
                var joinTitle = 'Search results for: ';
                var tableSelected = '';
                
                joinTitle += tableTitle + ' in ' + (reportType == 'national' ? 'the United States' : getStateYearArray('state', reportType)) + ' during years: ' + getStateYearArray('year', reportType) ;
                //console.log("Payer names: ");
                //console.log(payerNames);
                return joinTitle;
            }
            
            $(document).on('click','.singletable tbody tr', function() {
                var reportType = reportURL.get('report');
                var tableindex = $("tr", $(this).closest("tbody")).index(this);
                var chartfind = $(this).closest('.singletable');
                var charttype = chartfind.hasClass('num') ? 'num' :(chartfind.hasClass('perc') ? 'perc': 'dol');
                if(reportType == 'single') drawSDvisualization('changeChart',tableindex,charttype);
            
            
            });
            
            function getReportVar(calltype) {
                var urlid = reportURL.get('showchart');
                console.log("URL segment: " + urlid);
                
            return $.ajax({
                    url: '/themes/custom/thinkwork8_boot/chartbuilder/sd_chartcall.php?getstoredvar=' + calltype + '&urlid=' + urlid,
                    beforeSend: function(){
                        $('#chart_div_0').html('<img class="loadingImg" src="/themes/custom/thinkwork8_boot/img/spinner.gif">');
                    },
                    type: "GET",
                    dataType: "json",
                    global: false,
                    async:false,
                    cache: false,
                    success: function(response) {
            
                    
            
                }
                ,
                error: function(xhr, status, err) {
                    return false;
                }
                }).responseText;
            }
            
            function drawSDvisualization(changeChart,tableindex,numpercdol) {
                
            
                var titleDiv = $('#sdchart_table_div_0_title');
                var stackedType = 'percent';
                var reportVar = changeChart == 'storedChart' ? getReportVar('report') : reportURL.get('report');
                console.log('Report type from draw function: ' + reportVar);
                if(!changeChart || changeChart != 'changeChart'){
            
                    $(titleDiv).empty();
                    $('#sdchart_table_div_0').empty();
                    $('#sdchart_table_div_1').empty();
                    $('#sdchart_table_div_2').empty();
            
                    $('.clearable').empty();
                } 
                if(!numpercdol || numpercdol == 'num')$('#chart_div_0').empty();
                if(!numpercdol || numpercdol == 'perc') $('#chart_div_1').empty();
                if(!numpercdol || numpercdol == 'dol')$('#chart_div_2').empty();
                var legendArray = reportVar == 'single' ? getCheckboxLabels('single') : getCheckboxLabels('comparison');
                //console.log(legendArray);
                var legendHTML = legendBuild(legendArray);
                if((reportVar == 'single' && countChecks('single') && countChecks('single') < 12) || (reportVar == 'comparison' && countChecks('activity') && countChecks('activity') < 5)) { stackedType = true ; }
            
                var chartReturn = null; 
                var container = null;
                var inputCount = parseInt(countChecks('program') + (countChecks('program') > 0 ? 0 : countChecks('comparison')));
                var chartHeight = inputCount > 3 ? (inputCount > 5 ? (inputCount > 9 ? '900' : '600') : '500') : '250';
                var groupWid = inputCount > 3 ? (inputCount > 5 ? '22' : '32') : '22';
                
                var chartTitle = reportVar == 'single' ? buildTableTitle('single') : buildTableTitle('comparison');
                
                
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
                if(reportVar == 'single'){
                    var joinTitle = buildTableTitle('single');
                    var chartReturn = changeChart == 'storedChart' ? populatePrograms('storechart',tableindex,numpercdol) : populatePrograms('chart',tableindex,numpercdol); 
                    
                    if(changeChart == 'changeChart' && numpercdol){
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
                        // $(titleDiv).append('<h5>' + tableTitle + '</h5>');
                    } else {
                        $.each(chartReturn, function( index, value ) {
            
                            var tableTitle = joinTitle;
            
                            titleDiv = $('#sdchart_table_div_' +index + '_title');
                            var titleSuffix = index === 0 ? ': Numbers of people' : (index == 1 ? ': Percentages' : ': Dollars and Hours');
                            $(titleDiv).append('<h5>' + tableTitle + titleSuffix + '</h5>' + '<p>Select rows in the table to change the chart displayed above.</p>');
                            if(!chartReturn[index]) {
                                $('#chart_div_' + index).empty();
                                titleDiv.empty();
                                return false;
                            }
            
            
                        
                            
                            if(changeChart == 'storedChart') {
                                var timeout;
                        
                                timeout = setInterval(function () {
                                    if (google.visualization != undefined) {
                                        containerDiv = 'chart_div_' + index;
                                        container = document.getElementById(containerDiv);
                                        chartdata = new google.visualization.DataTable(chartReturn[index]);
                                        chart = new google.visualization.ColumnChart(container);
                                        chart.draw(chartdata,chartoptions);
                                    clearInterval(timeout);
                                    }
                                }, 300);
                            } else 
                            {
                                containerDiv = 'chart_div_' + index;
                                container = document.getElementById(containerDiv);
                                chartdata = new google.visualization.DataTable(chartReturn[index]);
                                chart = new google.visualization.ColumnChart(container);
                                chart.draw(chartdata,chartoptions);
                            }
                            
                            
                        });
            
                    }
                }
                else if(reportVar == 'national') {
                        
                    var joinTitle = buildTableTitle('comparison');
                    var natoptions = {
                        region: 'US',
                        displayMode: 'regions',
                        resolution: 'provinces',
                        width: '90%',
                        height: '600',
                        interpolateNulls: true,
                        defaultColor: '#f5f5f5',
            
                    };
                    var natChartTitleDiv = $('#nationalTitle');
                    $(natChartTitleDiv).append('<h6>' + joinTitle + '</h6>');
                    titleDiv = $('#sdchart_table_div_0_title');
                    $(titleDiv).append('<h5>' + joinTitle + '</h5>');
                
                    var chartReturn = changeChart == 'storedChart' ? populatePrograms('storechart') : populatePrograms('chart'); 
                    var containerDiv = 'chart_div_0';
                    var container = document.getElementById(containerDiv);
                    chartdata = new google.visualization.DataTable(chartReturn);
                    var chart = new google.visualization.GeoChart(container);
            
                        chart.draw(chartdata,natoptions);
                
                    
                    
                } else {
                        var joinTitle = buildTableTitle('comparison');
                        titleDiv = $('#sdchart_table_div_0_title');
                        $(titleDiv).append('<h5>' + joinTitle + '</h5>');
                        var chartReturn = changeChart == 'storedChart' ? populatePrograms('storechart') : populatePrograms('chart'); 
                            containerDiv = 'chart_div_0';
                            if(changeChart == 'storedChart') {
                                var timeout;
                                timeout = setInterval(function () {
                                    if (google.visualization != undefined) {
                                        container = document.getElementById(containerDiv);
                                        chartdata = new google.visualization.DataTable(chartReturn);
                                        chart = new google.visualization.ColumnChart(container);
                                        chart.draw(chartdata,chartoptions);
                                    clearInterval(timeout);
                                    }
                                }, 300);
                            } else 
                            {
                                container = document.getElementById(containerDiv);
                                chartdata = new google.visualization.DataTable(chartReturn);
                                chart = new google.visualization.ColumnChart(container);
                                chart.draw(chartdata,chartoptions);
                            }
            
                            
            
                }
            
                
            
            
            
                var dataReturn = [];
                if(changeChart != 'changeChart') {
                        dataReturn = changeChart == 'storedChart' ? populatePrograms('storetable') : populatePrograms('table');
                        var data = reportVar == 'single' ? new google.visualization.DataTable(dataReturn[0]) : new google.visualization.DataTable(dataReturn);
                        if (!$('#downCSV').hasClass('visible')) $('#downCSV').addClass('visible');
            
                        var table = new google.visualization.Table(document.getElementById('sdchart_table_div_0'));
                        var options = {
                                    'showRowNumber': false,
                                    'width':'95%',
                                    'allowHtml': true,
                                    'height':'100%'
                            };
                            table.draw(data, options);
            
                        if(reportVar == 'single') {
            
                            if(!dataReturn[1]) {
                                $('#sdchart_table_div_2').empty();
                                return;
                            }
                            var data = new google.visualization.DataTable(dataReturn[1]);
                            if (!$('#downCSV').hasClass('visible')) $('#downCSV').addClass('visible');
            
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
                                $('#sdchart_table_div_2').empty();
                                return;
                            }
                            var data = new google.visualization.DataTable(dataReturn[2]);
                            if (!$('#downCSV').hasClass('visible')) $('#downCSV').addClass('visible');
            
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
                    $('#' + tableTitleTarget).append('<h5><strong>' + reportHeader + ' ' + yearstext + '</strong></h5><p class="tableInstruct"><em>Select the table headings to sort table data</em></p>');
                    if ($('#' + tableTarget).hasClass('collapse')) $('#' + tableTarget).removeClass('collapse');
                    $('#' + tableTarget).attr('aria-labelledby', tableTitleTarget).attr('data-parent', '#summ_accordion').attr('aria-expanded', true);
            
                }
                else {
                    $('#' + tableTitleTarget).append('<h5><strong>' + reportHeader + ' in ' + statenametext + ' for ' + yearstext + '</strong></h5><p class="tableInstruct"><em>Select the table headings to sort table data</em></p>');
                    if ($('#' + tableTarget).hasClass('collapse')) $('#' + tableTarget).removeClass('collapse');
                    $('#' + tableTarget).attr('aria-labelledby', tableTitleTarget).attr('data-parent', '#summ_accordion').attr('aria-expanded', true);
            
                }
                q.send(function(response) {
                    var data = response.getDataTable();
                    var dataView = new google.visualization.DataView(data);
                    var numrows = dataView.getNumberOfRows();
                    if (numrows === 0 && i === 0 && !clearAll) {
                        $('#chart_div_0').prepend('<h5>Your query produced no results.  Try again.</h5>');
                        $('#' + tableTitleTarget + ' h5').remove();
                        $('#chart_div_0 > div').remove();
                        $('#legend_div').empty();
                        if($('button#spreadDL').hasClass('toggleShow')) $('button#spreadDL').removeClass('toggleShow');
                        if($('.switchNum').hasClass('toggleShow'))$('.switchNum').removeClass('toggleShow');
                        $('.dlHeading').hide();
                        if($('button#printButton').hasClass('toggleShow')) $('button#printButton').removeClass('toggleShow');
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
            $(document).on('change','input:radio[name="vrcat"]', function() {
            
                    if ($(this).is(':checked')) {
                    if(!$('div#vrvars').hasClass('showVars')) $('div#vrvars').addClass('showVars');
                    }
            });
            $(document).on('change','input:radio[name="acscat"]', function() {
            
                    if ($(this).is(':checked')) {
                    if(!$('div#acsvars').hasClass('showVars')) $('div#acsvars').addClass('showVars');
                    }
            });
            $(document).on('click','#accordion h5 button', function() {
            var accordParent = $(this).closest('.card');
            var parentID = accordParent.attr('id');
            /* Don't want to clear data source checks when closing accordion
            $('#' + parentID + ' input:radio[name="reportChoose"]').prop('checked', false);
            $('#' + parentID + ' input:radio[name="vrcat"]').prop('checked', false);
            $('#' + parentID + ' input:radio[name="acscat"]').prop('checked', false);
            $('#' + parentID + ' input:checkbox[name="reportMultiChoose[]"]').prop('checked', false); */
            if($('div#vrvars').hasClass('showVars')) $('div#vrvars').removeClass('showVars');
            if($('div#acsvars').hasClass('showVars')) $('div#acsvars').removeClass('showVars');
            });
            function updateSelectCount(checkType,tableVar) {
                var reportType = reportURL.get('report');
                if (checkType == 'year') {
                    var yeararray1 = getStateYearArray('year', reportType);
                    //if (yeararray1.length < 1 && reportURL[1] == 'natrep') return;
                    var years1 = reportURL.get('grp') == 'natrep' ? $('input[name="summChoose"]:checked').val() : yeararray1;
                    yearstext = reportURL.get('grp') != 'natrep' ? yeararray1.join(', ') : years1;
                    console.log('Year Array:' + years1);
                    $('#yearCountText').empty();
                    $('#summyearCountText').empty();
                    var yrsSelect = yearstext;
            
                    var yrsSelectText = yrsSelect;
                    $('#yearCountText').append(yrsSelectText);
                    $('#summyearCountText').append(yrsSelectText);
            
                }
                if (checkType == 'state') {
                var staSelectText;
                    var statearray1 = getStateYearArray('state',reportType);
            
                    var statenametext = statearray1.length < 1 ? ' ' : statearray1.join(", ");
                    // if (statearray1.length < 1) return;
                    $('#stateCountText').empty();
                    var staSelect = statenametext;
                    if (statenametext.length > 54) {
                        staSelect = "Selected: (" + countChecks('state') + ") ";
                    }
                    staSelectText = staSelect;
                    $('#stateCountText').append(staSelectText);
                }
            
                if (checkType == 'table') {
                    
                    var chosenText = '<span id="chosenText">Chosen: ' + tableVar + '</span>';
            
                    
                    $('.tableChosen').closest('.card').find('h5').append(chosenText);
                }
            
            
                if (checkType == 'variable') {
                    
                    var chosenText = '<span id="chosenText">Chosen: ' + tableVar + '</span>';
            
                    
                    $('.variableChosen').closest('.card').find('h5').append(chosenText);
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
        });


    }
  };
})(jQuery, Drupal);
