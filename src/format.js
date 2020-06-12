// Javascript functions for Tree Topics course format.

M.course = M.course || {};
M.course.format = M.course.format || {};

/**
 * Get sections config for this format
 *
 * The section structure is:
 * <ul class="treetopics">
 *  <li class="section">...</li>
 *  <li class="section">...</li>
 *   ...
 * </ul>
 *
 * @return {object} section list configuration
 */
M.course.format.get_config = function() {
    return {
        container_node : 'ul',
        container_class : 'treetopics',
        section_node : 'li',
        section_class : 'section'
    };
}

/**
 * Swap section
 *
 * @param {YUI} Y YUI3 instance
 * @param {string} node1 node to swap to
 * @param {string} node2 node to swap with
 * @return {NodeList} section list
 */
M.course.format.swap_sections = function(Y, node1, node2) {
    var CSS = {
        COURSECONTENT : 'course-content',
        SECTIONADDMENUS : 'section_add_menus'
    };

    var sectionlist = Y.Node.all('.' + CSS.COURSECONTENT + ' ' + M.course.format.get_section_selector(Y));
    // Swap menus.
    sectionlist.item(node1).one('.' + CSS.SECTIONADDMENUS).swap(sectionlist.item(node2).one('.' + CSS.SECTIONADDMENUS));
}

/**
 * Process sections after ajax response
 *
 * @param {YUI} Y YUI3 instance
 * @param {array} response ajax response
 * @param {string} sectionfrom first affected section
 * @param {string} sectionto last affected section
 * @return void
 */
M.course.format.process_sections = function(Y, sectionlist, response, sectionfrom, sectionto) {
    var CSS = {
        SECTIONNAME : 'sectionname'
    },
    SELECTORS = {
        SECTIONLEFTSIDE : '.left .section-handle .icon'
    };

    if (response.action == 'move') {
        // If moving up swap around 'sectionfrom' and 'sectionto' so the that loop operates.
        if (sectionfrom > sectionto) {
            var temp = sectionto;
            sectionto = sectionfrom;
            sectionfrom = temp;
        }

        // Update titles and move icons in all affected sections.
        var ele, str, stridx, newstr;

        for (var i = sectionfrom; i <= sectionto; i++) {
            // Update section title.
            var content = Y.Node.create('<span>' + response.sectiontitles[i] + '</span>');
            sectionlist.item(i).all('.' + CSS.SECTIONNAME).setHTML(content);
            // Update move icon.
            ele = sectionlist.item(i).one(SELECTORS.SECTIONLEFTSIDE);
            str = ele.getAttribute('alt');
            stridx = str.lastIndexOf(' ');
            newstr = str.substr(0, stridx + 1) + i;
            ele.setAttribute('alt', newstr);
            ele.setAttribute('title', newstr); // For FireFox as 'alt' is not refreshed.
        }
    }
}

M.recit = M.recit || {};
M.recit.course = M.recit.course || {};
M.recit.course.format = M.recit.course.format || {};
M.recit.course.format.TreeTopicsWebApi = class{
    constructor(){
        this.gateway = this.getGateway();

        this.post = this.post.bind(this);
        this.onError = this.onError.bind(this);
    }

    getGateway(){
        return `${M.cfg.wwwroot}/course/format/treetopics/Gateway.php`;
    }

    onError(jqXHR, textStatus) {
        alert("Error on server communication (" + textStatus + ").\n\nSee console for more details");
        console.log(jqXHR);
    };

    post(url, data, callbackSuccess){
        data = JSON.stringify(data);

        let xhr = new XMLHttpRequest();
        xhr.open("post", url, true);
        // Header sent to the server, specifying a particular format (the content of message body).
        xhr.setRequestHeader('Content-Type', 'application/json; charset=utf-8');
        xhr.setRequestHeader('Accept', 'json'); // What kind of response to expect.

        xhr.onload = function(event){
            if(this.clientOnLoad !== null){
                let result = null;

                try{
                    result = JSON.parse(event.target.response);
                }
                catch(error){
                    console.log(error, this);
                }

                callbackSuccess.call(this, result);
            }
        }

        xhr.onerror = this.onError;

        xhr.send(data);
    }

    setSectionLevel(data, onSuccess){
        let options = {};
        options.data = data;
        options.service = "set_section_level";
        this.post(this.gateway, options, onSuccess);
    }

    setSectionContentDisplay(data, onSuccess){
        let options = {};
        options.data = data;
        options.service = "set_section_content_display";
        this.post(this.gateway, options, onSuccess);
    }

    getSectionContent(data, onSuccess){
        let options = {};
        options.data = data;
        options.service = "get_section_content";
        this.post(this.gateway, options, onSuccess);
    }
}

M.recit.course.format.TreeTopicsUtils = class{
    static getCookie(cname) {
        let name = cname + "=";
        let decodedCookie = decodeURIComponent(document.cookie);
        let ca = decodedCookie.split(';');
        for(let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) == ' ') {
                c = c.substring(1);
            }
            if (c.indexOf(name) == 0) {
                return c.substring(name.length, c.length);
            }
        }
        return "";
    }

    static setCookie(id, value, minutesExpire) {
        minutesExpire = minutesExpire || 1440;
        let d = new Date();
        d.setTime(d.getTime() + (minutesExpire * 60 * 1000));
        let expires = "expires=" + d.toUTCString();
        document.cookie = id + "=" + value + "; " + expires;
    };

    static getUrlVars(){
        let vars, uri;

        vars = {};

        uri = decodeURI(window.location.href);

        let parts = uri.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
            vars[key] = value;
        });

        return vars;
    }
}

M.recit.course.format.TreeTopicsEditingMode = class{
    constructor(webApi){
        this.onChangeFilter = this.onChangeFilter.bind(this);
        this.onChangeLevel = this.onChangeLevel.bind(this);
        this.onChangeContentDisplay = this.onChangeContentDisplay.bind(this);

        this.webApi = new M.recit.course.format.TreeTopicsWebApi();

        this.filter = null;

        this.init();
    }

    init(){
        this.initRadioSectionLevel();
        this.initRadioSectionContentDisplay();
        this.initFilter();
    }


    getQueryVariable(name){
        let query = window.location.search.substring(1);
        let vars = query.split("&");
        for (let i = 0; i < vars.length; i++) {
            let pair = vars[i].split("=");
            if(pair[0] == name){return pair[1];}
        }
        return(false);
    }

    initRadioSectionLevel(){
        let sectionList = document.querySelectorAll("[data-section-id]");

        for(let section of sectionList){
            let radioItems = section.querySelectorAll("[data-component='ttRadioSectionLevel']");

            for(let item of radioItems){
                item.onchange = (event) => this.onChangeLevel(event.target, section);
            }
        }
    }

    onChangeLevel(radio, section){
        let callback = function(result){
            if(result.success){
                section.setAttribute("data-section-level", radio.value);
            }
            else{
                alert(M.recit.course.format.TreeTopics.messages.error);
            }
        }
        let courseId = this.getQueryVariable("id");
        this.webApi.setSectionLevel({courseId: courseId, sectionId: section.getAttribute("data-section-id"), level: radio.value}, callback);
    }

    initRadioSectionContentDisplay(){
        let sectionList = document.querySelectorAll("[data-section-id]");

        for(let section of sectionList){
            let radioItems = section.querySelectorAll("[data-component='ttRadioSectionContentDisplay']");

            for(let item of radioItems){
                item.onchange = (event) => this.onChangeContentDisplay(event.target, section);
            }
        }
    }

    onChangeContentDisplay(radio, section){
        let callback = function(result){
            if(!result.success){
                alert(M.recit.course.format.TreeTopics.messages.error);
            }
        }
        let courseId = this.getQueryVariable("id");
        this.webApi.setSectionContentDisplay({courseId: courseId, sectionId: section.getAttribute("data-section-id"), value: radio.value}, callback);
    }

    initFilter(){
        this.filter = document.getElementById("ttModeEditionFilter");

        if(this.filter === null){ return; }

        let options = this.filter.querySelectorAll("input");

        for(let item of options){
            item.onchange = this.onChangeFilter;
            let evt = document.createEvent("HTMLEvents");
            evt.initEvent("change", false, true);
            item.dispatchEvent(evt);
        }
    }

    onChangeFilter(event){
        switch(event.target.value){
            case "act": this.displayActivities(event.target.checked); break;
            case "sum": this.displaySummary(event.target.checked); break;
        }

        // Cookies ctrl.
        let options = this.filter.querySelectorAll("input");

        let cookies = [];
        for(let item of options){
            if(item.checked){
                cookies.push(item.value);
            }
        }

        M.recit.course.format.TreeTopicsUtils.setCookie('ttModeEditionFilter', cookies.join(","));
    }

    displayActivities(display){
        let sectionList = document.querySelectorAll('[data-section-level]');

        for(let section of sectionList){
            let elList = [
                ...section.querySelectorAll("ul.section"),
                ...section.querySelectorAll("div.section-modchooser")
            ];

            for(let el of elList){
                el.style.display = (display ? "block" : 'none');
            }
        }

    }

    displaySummary(display){
        let sectionList = document.querySelectorAll('[data-section-level]');

        for(let section of sectionList){
            let elList = section.querySelectorAll("div.summary");
            for(let el of elList){
                el.style.display = (display ? "block" : 'none');
            }
        }
    }
}

M.recit.course.format.TreeTopics = class{
    constructor(){
        this.getSectionContentResult = this.getSectionContentResult.bind(this);

        this.webApi = new M.recit.course.format.TreeTopicsWebApi();

        this.sectionContent = null;
        this.pagination = null;

        this.init();
    } 

    init(){
        let params = M.recit.course.format.TreeTopicsUtils.getUrlVars();

        // If there is no sectionId defined then it displays the section-0.
        let sectionId = params.sectionId || M.recit.course.format.TreeTopicsUtils.getCookie('section') || 'section-0';
        
        this.pagination = document.getElementById('sectionPagination');

        this.sectionContent = document.getElementById("sectioncontent_placeholder");

        this.goToSection(null, sectionId);

        this.ctrlPagination();
    }

    ctrlMenuM1(sectionId){
        let menu = document.getElementById("tt-recit-nav");

        if(menu === null){ return;}

        let selectMenuItem = function(id){
            let el = menu.querySelector(`[data-section=${id}]`);
            if(el !== null){
                el.parentElement.setAttribute("data-selected", "1");
            }

            // If the menu level1 item has a branch then it also select it.
            let branch = menu.querySelector(`[data-parent-section=${id}]`);
            if(branch !== null){
                el.parentElement.setAttribute("data-selected", "1");
                el.previousElementSibling.style.display = 'none'; // Remove the arrow on parent element.
                branch.setAttribute("data-selected", "1");
            }

            return el;
        }

        // Reset menu level 1 selection.
        let elems = menu.getElementsByClassName('menuM1-item');
        for(let el of elems){
            el.setAttribute("data-selected", "0");
        }

        // Reset menu level 2 selection.
        elems = menu.querySelectorAll('[data-parent-section]');
        for(let el of elems){
            el.setAttribute("data-selected", "0");
        }

        // Select menu level1 item.
        let selectedElem = selectMenuItem(sectionId);

        // Select menu level2 item.
        if(selectedElem){
            let parentSectionId = selectedElem.parentElement.parentElement.getAttribute("data-parent-section");
            selectMenuItem(parentSectionId);
        }
    }

    getSectionContentResult(result){
        if(!result.success){
            alert(M.recit.course.format.TreeTopics.messages.error);
            console.log(result);
            return;
        }

        if(result.data === null){
            return;
        }

        let doc = new DOMParser().parseFromString(result.data, "text/html");
        
        while (this.sectionContent.lastElementChild) {
            this.sectionContent.removeChild(this.sectionContent.lastElementChild);
        }
        
        this.sectionContent.appendChild(doc.body.firstChild);
    }

    goToSection(event, sectionId) {
        sectionId = sectionId || '';

        if(event !== null){
            event.preventDefault();
            sectionId = event.currentTarget.getAttribute('data-section');
        }

        if(sectionId.length === 0){
            return;
        }

        this.ctrlMenuM1(sectionId);

        document.cookie = 'section=' + sectionId;

        let params = M.recit.course.format.TreeTopicsUtils.getUrlVars();
        
        if(this.sectionContent !== null){
            this.webApi.getSectionContent({sectionid: sectionId, courseid: params.id}, this.getSectionContentResult);
        }

        // Simulate the menu button click to hide the menu (mode mobile).
        let btn = document.querySelector("[data-target='#navbarTogglerCourse']");
        if((btn !== null) && (btn.getAttribute('aria-expanded') === 'true')){
            btn.click();
        }

        this.ctrlPagination();
    }

    ctrlPagination(){
        if(this.pagination === null){ return; }

        let navbar = document.getElementById('tt-recit-nav');
        let sections = navbar.querySelectorAll('[data-section');

        let currentSection = M.recit.course.format.TreeTopicsUtils.getCookie('section');
        let btnPrevious = this.pagination.firstChild.firstChild;
        let btnNext = this.pagination.firstChild.lastChild;

        let iSection = parseInt(currentSection.split("-").pop()) || 0;
        
        if(iSection <= 0){
            btnPrevious.classList.add("disabled");
        }
        else{
            btnPrevious.classList.remove("disabled");
            btnPrevious.firstChild.setAttribute('data-section', `section-${iSection-1}`);
        }

        if(iSection >= sections.length - 2){
            btnNext.classList.add("disabled");
        }
        else{
            btnNext.classList.remove("disabled");
            btnNext.firstChild.setAttribute('data-section', `section-${iSection+1}`);
        }

    }
}

// Definition static attributes and methods to work with Firefox.
M.recit.course.format.TreeTopics.messages = {
    error: "Une erreur inattendue est survenue. Veuillez réessayer."
}

// Without jQuery (doesn't work in older IEs).
document.addEventListener('DOMContentLoaded', function() {
    M.recit.course.format.TreeTopics.instance = new M.recit.course.format.TreeTopics();
    M.recit.course.format.TreeTopicsEditingMode.instance = new M.recit.course.format.TreeTopicsEditingMode();
}, false);