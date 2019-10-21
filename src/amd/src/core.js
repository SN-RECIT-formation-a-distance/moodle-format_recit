// This file is part of a plugin written to be used on the free teaching platform : Moodle
// Copyright (C) 2019 recit
// 
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
// 
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// 
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/>.
//
// @package    format_treetopics
// @subpackage RECIT
// @copyright  RECIT {@link https://recitfad.ca}
// @author     RECIT {@link https://recitfad.ca}
// @license    {@link http://www.gnu.org/licenses/gpl-3.0.html} GNU GPL v3 or later
// @developer  Studio XP : {@link https://www.studioxp.ca}

define(['jquery'], function($) {
    return {
        init: function() {
            function getCookie(cname) {
              var name = cname + "=";
              var decodedCookie = decodeURIComponent(document.cookie);
              var ca = decodedCookie.split(';');
              for(var i = 0; i <ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0) == ' ') {
                  c = c.substring(1);
                }
                if (c.indexOf(name) == 0) {
                  return c.substring(name.length, c.length);
                }
              }
              return "";
            }
            $('.tt-section-image-link').click(function() {
                var dataValue = $(this).attr('data');
                var backButton = $('#tt-section-image-back');
                if($(this).attr('id') == 'tt-section-image-back')
                {
                    backButton.css('display', 'none');
                    $('#' + backButton.attr('data')).css('display', 'grid');
                    $('.tt-section').css('display', 'none');
                    $('#tt-maintabs').css('display', 'block');
                }
                else
                {
                    var sectionID = '#' + dataValue;
                    backButton.attr('data', $(this).parent().attr('id'));
                    backButton.css('display', 'block');
                    $('.tt-imagebuttons').css('display', 'none');
                    $(sectionID).css('display', 'block');
                    $('#tt-maintabs').css('display', 'none');
                }
            });
            $('#tt-contract-read').click(function() {
                $('#tt-contract-sign').attr('disabled', !$(this).prop('checked'));
            });
            $('#tt-contract-sign').click(function() {
                window.location = $('#tt-contract-sign').attr('href');
            });
            $('#tt-recit-nav a').on('click', function () {
                var datasection = $(this).attr('data-section');
                if (typeof datasection !== typeof undefined && datasection !== false) {
                    var sectionID = '#' + datasection;
                    document.cookie = 'section=' + sectionID;
                    $('.tt-section').css('display', 'none');
                    $('.tt-imagebuttons').css('display', 'none');
                    $(sectionID+'-imagebuttons').css('display', 'grid');
                    $(sectionID).css('display', 'block');
                }
            });
            $('.dropdown-menu a.dropdown-toggle').on('click', function() {
                if (!$(this).next().hasClass('show'))
                {
                    $(this).parents('.dropdown-menu').first().find('.show').removeClass("show");
                }
                var $subMenu = $(this).next(".dropdown-menu");
                $subMenu.toggleClass('show');
                $(this).parents('li.nav-item.dropdown.show').on('hidden.bs.dropdown', function()
                {
                    $('.dropdown-submenu .show').removeClass("show");
                });
                return false;
            });
            $(window).ready(function() {
                var sectionID = getCookie('section');
                if(sectionID !== '')
                {
                    $('.tt-section').css('display', 'none');
                    $('.tt-imagebuttons').css('display', 'none');
                    $(sectionID+'-imagebuttons').css('display', 'grid');
                    $(sectionID).css('display', 'block');
                }
            });
        }
    };
});