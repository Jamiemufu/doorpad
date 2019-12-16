// var lang = 1;
// var voice = 4;  //bridget
// var engine = 3;

var rvVoice = "UK English Female";

    /* process a sign out click here */
    $('a.signout').click(function(e) {
        e.preventDefault();
        /*
            get id
            ajax call to delete from db
            on success
                remove row from DOM

        */
        var id = $(this).data('signout');
        var firstname = $(this).data('firstname');
        var formData = {
            'id' : id,
            'firstname' : firstname,
        };

        var text = 'goodbye ' + firstname;
        //sayText(text, voice, lang, engine);
        console.log('goodbye calling responsiveVoice');
        responsiveVoice.speak(text);

        $.ajax({
            url: _whsky.link('PageController','logout'),
            type : 'POST',
            dataType : 'json',
            cache: false,
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
            headers: {
                "cache-control": "no-cache"
            },
            data : formData,
            success : function(data)
            {
                $('#' + id).fadeOut('slow', function() {
                    $(this).remove();
                });
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log("logout ajax error: textStatus: %s errorThrown %s", textStatus, errorThrown);
            },
            complete: function(jqXHR, textStatus) {
                console.log("logout ajax complete: %s", textStatus)
            }
        });

        return false;
    });

    /* Process Login button here */
    $('#login').click(function(e) {
        e.preventDefault();
        var formData = {
            // 'csrf_token' : $('input[name=csrf_token]').val(),
            'firstname' : $('input[name=firstname]').val(),
            'lastname' : $('input[name=lastname]').val(),
            'badge' : $('input[name=badge]').val(),
            'company' : $('input[name=company]').val(),
            'visiting' : $('input[name=visiting]').val(),
            'carReg' : $('input[name=carReg]').val(),
        };

        var welcome = getWelcome();
        var text = welcome + ' ' + formData.firstname;

        // sayText(text, voice, lang, engine);
        // console.log(text);
        // e.preventDefault();
        
        //valiadte and add onto error string for voice
        var errors = "Please enter your ";

        if (formData.firstname.length == 0)
        {
            errors = errorCheck(errors, "first name");
        }

        if (formData.lastname.length == 0)
        {
            errors = errorCheck(errors, "last name");
        }

        if (formData.badge.length == 0)
        {
            errors = errorCheck(errors, "badge number");
        }

        if (formData.carReg.length == 0)
        {
            errors = errorCheck(errors, "car registration");
        }

        if (errors !== 'Please enter your ') {
            responsiveVoice.speak( errors, rvVoice );
            alert(errors);
            
            return false;
        }

        $.ajax({
            url: _whsky.link('PageController','login'),
            type : 'POST',
            dataType : 'json',
            data : formData,
            cache: false,
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
            async: false,
            timeout: 15000,
            headers: {
                "cache-control": "no-cache"
            },
            success : function(data)
            {
                console.log('login ajax success - completed');
                // window.location.href = _whsky.link('PageController','home');

            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log("login ajax error: textStatus: %s errorThrown %s", textStatus, errorThrown);
            },
            complete: function(jqXHR, textStatus) {
                console.log("login ajax complete: %s", textStatus)
            }            
        });

        //sayText(text, voice, lang, engine);
        console.log('calling responsiveVoice');

        responsiveVoice.speak(text);
        console.log(text);

        //clear text fields
        $('form.login')[0].reset();

        console.log('after ajax function about to return false')
        //uncomment to stay on login screen
        //return false;

        //redirect to home
        window.location.href = _whsky.link('PageController','home');

    });

    //checking error string
    function errorCheck(errors, string) {
        if (errors == 'Please enter your ') {
            errors += string;
        }
        else {
            errors += ', ' + string;
        }
        return errors;
    }

    //clock display
    function startTime() {
        var month_name = new Array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
        var day_name = new Array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
        var today = new Date();
        var month = today.getMonth();
        var date = today.getDate();
        var day = today.getDay();
        var h = today.getHours();
        var m = today.getMinutes();
        var s = today.getSeconds();
        m = checkTime(m);
        s = checkTime(s);

        var suffix = ' ';

        if (date == 1 || date == 21 || date == 31 )
        {
            suffix = 'st';
        }
        else if (date == 2 || date == 22)
        {
            suffix = 'nd';
        }
        else if (date == 3 || date == 23 )
        {
            suffix = 'rd';
        }
        else
        {
            suffix = 'th';
        }

        //only enable clock if div exists
        if ($('#clock').length)
        {
            document.getElementById('clock').innerHTML =
            '<span class="clock-date">' + day_name[day] + ' ' + date + suffix + ' ' + month_name[month] + '</span> <span class="clock-time">' + h + ":" + m + ":" + s + '</span>';
            var t = setTimeout(startTime, 500);        
        }
    }

    function checkTime(i) {
        if (i < 10) {i = "0" + i};  // add zero in front of numbers < 10
        return i;
    }


    function getWelcome()
    {
        var today = new Date();
        var currentHour = today.getHours();
        var greeting = new Array('Hello', 'Welcome', 'Greetings', 'Welcome to I T G');

        if (currentHour <= 11) {
            greeting.push('Good Morning');          
        } 
        else if (currentHour >= 12 && currentHour <= 17) {
            greeting.push('Good Afternoon');       
        }
        else {
            greeting.push('Good Evening');
        }

        var i = Math.floor((Math.random()*greeting.length));

        return greeting[i];
    }

    $(window).on('load', function(){
        startTime();
    });
