$(document).ready(function() {
    var $dragging = null;
    var $resizeE = null;
    var $resizeW = null;
    var $initPosition = {offsetY: null, offsetX: null};
    var $chngHeight = 0;
    var $lastPosition = {top: null, left: null};
//    $('image.plan, img.plan').on('dragstart', function(event) { event.preventDefault(); });

    $( window ).resize(function() {
       initCalendar();
    });


    $(document.body).on("mousemove", function(e) {
        //return;
        $lastPosition.left = e.pageX;
        $lastPosition.top = e.pageY;
        //$canvas = $resizeE.parent().parent().parent().parent().parent().parent().parent().parent('.panel-body-fullsize');
        $canvas = $('#canvas');
        if ($resizeE ) {
            $resizeE.tooltip('hide');
            $newLeft = parseInt(e.pageX);
            $minLeft = parseInt($resizeE.offset().left) + parseInt($resizeE.css('width')) - 10;
            $maxLeft = parseInt($canvas.css('width')) + $minLeft - parseInt($resizeE.css('width')) + 10;
            $newWidth = parseInt(e.pageX) - parseInt($resizeE.offset().left);
            //console.log('newWidth: ' + $newWidth);
            $resizeE.css('width', $newWidth);


        }
        if ($dragging && ($dragging.hasClass('celitem'))) {
            $dragging.tooltip('hide');
            //$canvas = $dragging.parent().parent().parent('.panel-body-fullsize');
            $newTop = parseInt(e.pageY - $initPosition.offsetY);
            $newLeft = parseInt(e.pageX - $initPosition.offsetX);
            //$newTop = parseInt(e.pageY );
            //$newLeft = parseInt(e.pageX );

            $minTop = $canvas.offset().top;
            $minLeft = $canvas.offset().left;

            $maxTop = parseInt($canvas.css('height')) + $minTop - parseInt($dragging.css('height'));
            $maxLeft = parseInt($canvas.css('width')) + $minLeft - parseInt($dragging.css('width')) + 10;
            if ( $newTop >= $minTop && $newTop <= $maxTop) {
                $setTop = $newTop;
            } else {
                if ($newTop <= $minTop) {
                    $setTop = $minTop;
                }
                if ($newTop >= $maxTop) {
                    $setTop = $maxTop;
                }
            }

            if ( $newLeft >= $minLeft && $newLeft <= $maxLeft){
                $setLeft = $newLeft;
            }else {
                if ($newLeft <= $minLeft) {
                    $setLeft = $minLeft;
                }
                if ($newLeft >= $maxLeft) {
                    $setLeft = $maxLeft;
                }
            }
            $dragging.offset({
                top: $setTop,
                left: $setLeft,
            });

            /*$oContainer = $dragging.next();
            $left = parseInt($dragging.css('left')) + 25;
            $top = parseInt($dragging.css('top'));
            $oContainer.css('left', $left);
            $oContainer.css('top', $top);*/

        }
    });

    $(document.body).on("mouseover", ".celitem", function (e) {
         //console.log('prejel jsem celitem');
         //$(e.target).css('cursor','e-resize');
        /*$testTarget = $(e.target);
        $borderArea = $testTarget.offset().left + parseInt($testTarget.css('width')) - 30;
        if (e.pageX >= $borderArea) {
            $(e.target).css('cursor', 'e-resize');
        }else{
            $(e.target).css('cursor', 'default');
        }*/
    });

    $(document.body).on("mouseover", ".calrow", function (e) {
        //console.log('prejel jsem calrow');
       // $(e.target).css('cursor','default');
    });

    // Timeout, started on mousedown, triggers the beginning of a hold
    var holdStarter = null;
    // Milliseconds to wait before recognizing a hold
    var holdDelay = 250;
    // Indicates the user is currently holding the mouse down
    var holdActive = false;

    $(document.body).on("mousedown", ".celitem", function (e) {
        holdStarter = setTimeout(function() {
            holdStarter = null;
            holdActive = true;
            // begin hold-only operation here, if desired
        }, holdDelay);
        //console.log('celitem mousedown');

        $testTarget = $(e.target);


        $resizeE = null;
        $borderArea = $testTarget.offset().left + parseInt($testTarget.css('width')) - 20;
        console.log('e.PageX: ' + e.pageX);
        console.log('borderArea: ' + $borderArea);
        if (e.pageX >= $borderArea && $testTarget.hasClass('emark')){
            $testTarget = $testTarget.parent();
            $resizeE = $testTarget;
            $testTarget.css('cursor','e-resize');
            $resizeE.find('.emark').hide();
            $chngHeight = 0;
        }else{
            if ($testTarget.hasClass('celitem')) {
                $dragging = $testTarget;
                $dragging.css('cursor', 'pointer');
                //$testTarget.css('cursor', 'default');
                //
            }
        }
        $testTarget = null;
        console.log('resizeE');
        console.log($resizeE);

        if ($dragging != null) {
            $initPosition.offsetY = e.pageY - $dragging.offset().top;
            $initPosition.offsetX = e.pageX - $dragging.offset().left;
        }

        window.addEventListener('selectstart', disableSelect);
        //e.stopPropagation();
    });

    $(document.body).on("mouseup", function (e) {
        // If the mouse is released immediately (i.e., a click), before the
        //  holdStarter runs, then cancel the holdStarter and do the click
        if (holdStarter) {
            clearTimeout(holdStarter);
            // run click-only operation here
            $nodragging = $(e.target);
            if ($dragging) {
                $dragging.css('cursor', 'default');
                $dragging = null;
            }
        }
        // Otherwise, if the mouse was being held, end the hold
        else if (holdActive) {
            holdActive = false;
            $objectUpdate = null;
            // end hold-only operation here, if desired
            if ($resizeE){
                if ($chngHeight == 0) {
                    $resizeE.find('.emark').show();
                    $oldMarginTop = $resizeE.find('.emark').css('margin-top');
                    if (parseInt($resizeE.css('height')) >= 48 && parseInt($oldMarginTop) == 0) {
                        $resizeE.find('.emark').css('margin-top', '-20px');
                        $chngHeight = 1;
                    }
                    if (parseInt($resizeE.css('height')) <= 28 && parseInt($oldMarginTop) == -20) {
                        $resizeE.find('.emark').css('margin-top', '0px');
                        $chngHeight = 1;
                    }else{
                        //          $chngHeight = 0;
                    }

                }

                //$target = $(e.target);
                $centerX = $lastPosition.left;
                $centerY = $lastPosition.top;
                $arrUnder = getElsAt( $centerY, $centerX, 'calcel', $resizeE.prop('id'));
                if ($arrUnder.length > 0)
                {
                    $under = $('#'+$arrUnder[0].id);
                    $endPos = $under.offset().left + parseInt($under.css('width')) - 6;
                    $toEndX = $endPos - $centerX;
                    $endWidth = parseInt($resizeE.css('width')) + $toEndX;
                    $endVal = $under.data().end_value;
                    $resizeE.data('end_value', $endVal);
                    $resizeE.css('width', $endWidth);
                }
                $resizeE.css('cursor', 'default');
                $objectUpdate = $resizeE;
                $resizeE = null;
            }

            if ($dragging)
            {
                $dragging.css('cursor','default');
                $target = $(e.target);
                //$centerX = $dragging.offset().left +  parseInt($dragging.css('width')) / 2;
                //$centerY = $dragging.offset().top +  parseInt($dragging.css('height')) / 2;
                $centerX = $dragging.offset().left + 10;
                $centerY = $dragging.offset().top + 10;
                $arrUnder = getElsAt( $centerY, $centerX, 'celitem', $dragging.prop('id'));
                //console.log($arrUnder);
                $startVal = 0;
                $diff = 0;
                if ($arrUnder.length == 0) {
                    $arrUnder = getElsAt($centerY, $centerX, 'calcel', $dragging.prop('id'));
                    $under = $arrUnder[0];
                    $under2 = $('#'+$under.id);
                    $diff = new Date($target.data().end_value).getTime() - new Date($target.data().start_value).getTime();
                    $startVal = $under2.data().start_value;
                    //$target.data('start', $startVal);
                    //$target.data('end', $startVal + $diff);
                }

                //console.log($arrUnder);

                //$under = document.elementFromPoint($target.offset().left, $target.offset().top);
                $under = $arrUnder[0];
                //console.log($under);
                $under2 = $('#'+$under.id);
                //$dragging.insertBefore($under);
                $newTarget = $target.clone();
                $newTarget.css('left','auto');
                $newTarget.css('top','auto');
                $newTarget.css('z-index', '100');
                $target.hide('slow').remove();


                if ($under2.hasClass('celitem')) {
                    $($newTarget.hide()).insertAfter('#' + $under.id).show('fast');
                }else{
                    $($newTarget.hide()).appendTo('#' + $under.id).show('fast');
                }

                if ($startVal != "" && $diff >= 0) {
                    $newTarget.data('start_value', $startVal);
                    $newDate = new Date(new Date($startVal).getTime() + $diff);
                    $newTarget.data('end_value', $newDate.dateFormat('Y-m-d H:i:s'));
                }
                //append .emark if the class is moved from tasks
                if ($newTarget.find('.emark').length == 0){
                    $strHtml = $newTarget.html() + '<div class="emark"></div>';
                    $newTarget.html($strHtml);
                    if( parseInt($newTarget.css('height')) > 28 ){
                        $newTarget.find('.emark').css('margin-top', '-20px');
                    }else{
                        $newTarget.find('.emark').css('margin-top', '0px');
                    }
                }
                $objectUpdate = $newTarget;
                $newTarget.tooltip()
                $newTarget = null;
                $dragging = null;

                //e.stopPropagation();
            }
            //return;

            var objConfig = jQuery.parseJSON(jQuery('#calendarconfig').text());
            var url = objConfig.calUpdate;
            var dataJson = { calendar_plane_id: null , cl_commission_task_id: null, date_start: null, date_end: null};

            dataJson.calendar_plane_id              = $objectUpdate.data().id;
            dataJson.cl_commission_task_id          = $objectUpdate.data().commission_task_id;
            dataJson.date_start = $objectUpdate.data().start_value;
            dataJson.date_end   = $objectUpdate.data().end_value;


            $.ajax({
                url: url,
                method: 'post',
                type: 'json',
                data: dataJson,
                success: function(data) {
                    $objectUpdate.data('calendar_plane_id', data['retId'])
                    $objectUpdate = null;
                }
            });


            window.removeEventListener('selectstart', disableSelect);

        }
    });


});

function getElsAt(top, left, select, exclude){

    if (exclude == ""){
        exclude = "xx999";
    }

    return $("body")
        .find("div."+select+":not(#"+exclude+")")
        .filter(function() {
            result = $(this).offset().top <= top && ($(this).offset().top + parseInt($(this).css('height')) >= top)
                && $(this).offset().left <= left && ($(this).offset().left + parseInt($(this).css('width')) >= left);
            /*if (result) {
                console.log('top: ' + top);
                console.log($(this).offset().top);
                console.log($(this).offset().top + parseInt($(this).css('height')));
                console.log('left: ' + left);
                console.log($(this).offset().left);
                console.log($(this).offset().left + parseInt($(this).css('width')));
                console.log($(this));
            }*/
            if (select == "celitem") {
                $padding_total = 10;
            }else{
                $padding_total = 5;
            }
            $padding_total = 0;

            return $(this).offset().top <= top && ($(this).offset().top + parseInt($(this).css('height')) - $padding_total >= top)
                && $(this).offset().left <= left && ($(this).offset().left + parseInt($(this).css('width')) - $padding_total >= left);
        });
}

function disableSelect(event) {
    event.preventDefault();
}

function showContainer(myObj){
    $oContainer = myObj.next();
    $left = parseInt(myObj.css('left')) + 25;
    $top = parseInt(myObj.css('top'));
    $oContainer.css('left', $left);
    $oContainer.css('top', $top);
    if ($oContainer.css('display') == 'block') {
        $oContainer.css('display', 'none');
        $oContainer.css('opacity', '0');
        //$(this).css('display', 'none');
        /*$oContainer.animate({
            opacity: "0",
            }, 0, function () {
        });*/
    }else{
        $oContainer.css('display', 'block');
        $oContainer.animate({
            opacity: "1"
        }, 500);
    }
}

function initCalendar(){
    $('.calrow .calcel .celitem').each(function(index) {
        $startX = $(this).offset().left;
        //$endId = $(this).data().end;
        $endVal = $(this).data().end_value;
        $endId = null;
        $(this).closest('.calrow').find('.calcel.celcontent').each(function(index){
               $itemDate = new Date($endVal);
               $startDate = new Date($(this).data().start_value);
               $endDate = new Date($(this).data().end_value);
               if ($itemDate >= $startDate && $itemDate <= $endDate){
                   $endId = $(this).prop('id');
               }
        });

        if ($endId == null)
        {
            $endId = $(this).closest('.calrow').find('.calcel.celcontent').last().prop('id');
        }
        $endCol = $('#' + $endId);
        $end = $endCol.offset().left + parseInt($endCol.css('width')) - 10;

        //$centerX = $(this).offset().left + $end - 10;
        $centerX = $end;
        $centerY = $(this).offset().top;
        $arrUnder = getElsAt( $centerY, $centerX, 'calcel', 'xx');
        if ($arrUnder.length > 0){
            $under = $('#'+$arrUnder[0].id);
            $endPos = $under.offset().left + parseInt($under.css('width')) - 5;
            $toEndX = $endPos - $startX;
            //$endWidth = parseInt($(this).css('width')) + $toEndX;
            $endWidth = $toEndX;
            $(this).css('width', $endWidth);
        }
        if( parseInt($(this).css('height')) > 28 ){
            $(this).find('.emark').css('margin-top', '-20px');
        }else{
            $(this).find('.emark').css('margin-top', '0px');
        }
    });

    //set correct height for list of tasks
    $sumCalsHeight = 0;
    $('.calendar').each(function(index){
        $sumCalsHeight = $sumCalsHeight + parseInt($(this).css('height'));
        }
    );
    $('#tasks').css('height',$sumCalsHeight);


}
