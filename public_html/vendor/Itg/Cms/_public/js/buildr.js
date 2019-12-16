var UsersOnline = {};


/**
 * Update the 'users online' list
 * @param {object} user_list List of users
 */
UsersOnline.update = function(user_list)
{

    if (typeof user_list !== 'object')
    {
        throw 'Invalid user list';
    }

    var online_list = '';

    for (var i in user_list)
    {

        if (user_list.hasOwnProperty(i))
        {
            online_list += '<li class="online"><div class="media"><a href="' + _whsky.link('Itg\\Cms\\Http\\Controller\\AccountController', 'view_user', user_list[i].id) + '" class="pull-left media-thumb"><img alt="" src="' + user_list[i].icon + '" class="media-object"></a><div class="media-body"><strong>' + user_list[i].username + '</strong><small>' + user_list[i].role + '</small></div></div></li>';
        }

    }

    if (online_list == '')
    {
        online_list = '<li class="online"><small>No other users are online</small></li>';
    }

    $('#online_users_list').html(online_list);

};


var Ping = {};


Ping.timeout = 15000;


/**
 * Schedule a new ping
 */
Ping.schedule = function()
{

    setTimeout(function()
    {
        Ping.send();
    }, Ping.timeout);

};


/**
 * Send a ping
 */
Ping.send = function()
{

    $.ajax({

        type: 'GET',
        url:  _whsky.link('Itg\\Cms\\Http\\Controller\\AccountController', 'ping'),

        success: function(response)
        {
            UsersOnline.update(response);
            Ping.schedule();
        },

        error: function()
        {
            Ping.schedule();
        }

    });

};


$(document).ready(function()
{

    Ping.send();

});