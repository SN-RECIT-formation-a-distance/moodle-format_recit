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
            $('.tt-tabs').click(function() {
                var dataValue = $(this).attr('data');
                var sectionID = '#' + dataValue;
                $('.tt-section').css('display', 'none');
                $('.tt-imagebuttons').css('display', 'none');
                if($(this).hasClass('tt-tabs-lev1'))
                {
                    $('.tt-tabs-block-lev2').css('display', 'none');
                    $('.tt-tabs-block-lev3').css('display', 'none');
                }
                else if($(this).hasClass('tt-tabs-lev2'))
                {
                    $('.tt-tabs-block-lev3').css('display', 'none');
                }
                if($(sectionID).hasClass('tt-imagebuttons'))
                {
                    $(sectionID).css('display', 'grid');
                }
                else if(!$(sectionID).hasClass('tt-tabs-block-lev3'))
                {
                    $(sectionID).css('display', 'block');
                }
                else
                {
                    if($( "#tt-tabs-blocks-5" ).length)
                    {
                        $(sectionID).css('display', 'inline-flex');
                    }
                    else
                    {
                        $(sectionID).css('display', 'block');
                    }
                }
                if($(this).hasClass('tt-tabs-lev1'))
                {
                    $('.tt-tabs-lev1').removeClass('tt-tabs-selected');
                    $(this).addClass('tt-tabs-selected');
                    if($(sectionID).hasClass('tt-tabs-block-lev2'))
                    {
                        $(sectionID).children().first().trigger('click');
                    }
                    document.cookie = 'section1=' + dataValue;
                    document.cookie = 'section2=;';
                    document.cookie = 'section3=;';
                }
                else if($(this).hasClass('tt-tabs-lev2'))
                {
                    $('.tt-tabs-lev2').removeClass('tt-tabs-selected');
                    $(this).addClass('tt-tabs-selected');
                    if($(sectionID).hasClass('tt-tabs-block-lev3'))
                    {
                        $(sectionID).children().first().trigger('click');
                    }
                    document.cookie = 'section2=' + dataValue;
                    document.cookie = 'section3=;';
                }
                else if($(this).hasClass('tt-tabs-lev3'))
                {
                    $('.tt-tabs-lev3').removeClass('tt-tabs-selected');
                    $(this).addClass('tt-tabs-selected');
                    document.cookie = 'section3=' + dataValue;
                }
            });
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
            $(window).ready(function() {
                var section1 = getCookie('section1');
                var section2 = getCookie('section2');
                var section3 = getCookie('section3');
                if(section1 != '')
                {
                    $('#' + section1 + '-tab').trigger('click');
                    if(section2 != '')
                    {
                        $('#' + section2 + '-tab').trigger('click');
                        if(section3 != '')
                        {
                            $('#' + section3 + '-tab').trigger('click');
                        }
                    }
                }
                else
                {
                    $('#tt-tabs-block-lev1').children().first().trigger('click');
                }
            });
        }
    };
});
