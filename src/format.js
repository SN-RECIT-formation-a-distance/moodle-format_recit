// Javascript functions for Tree Topics course format

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

    var sectionlist = Y.Node.all('.'+CSS.COURSECONTENT+' '+M.course.format.get_section_selector(Y));
    // Swap menus.
    sectionlist.item(node1).one('.'+CSS.SECTIONADDMENUS).swap(sectionlist.item(node2).one('.'+CSS.SECTIONADDMENUS));
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
            sectionlist.item(i).all('.'+CSS.SECTIONNAME).setHTML(content);
            // Update move icon.
            ele = sectionlist.item(i).one(SELECTORS.SECTIONLEFTSIDE);
            str = ele.getAttribute('alt');
            stridx = str.lastIndexOf(' ');
            newstr = str.substr(0, stridx +1) + i;
            ele.setAttribute('alt', newstr);
            ele.setAttribute('title', newstr); // For FireFox as 'alt' is not refreshed.
        }
    }
}

M.recit = M.recit || {};
M.recit.course = M.recit.course || {};
M.recit.course.format = M.recit.course.format || {};
M.recit.course.format.TreeTopics = class{
    constructor(){
        this.onChangeFilter = this.onChangeFilter.bind(this);
        this.onChangeLevel = this.onChangeLevel.bind(this);
        this.onChangeContentDisplay = this.onChangeContentDisplay.bind(this);

        this.filter = null;
        this.pagination = null;

        this.init();
    }    

    getCookie(cname) {
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

    getUrlVars(){
        var vars, uri;

        vars = {};
    
        uri = decodeURI(window.location.href);
    
        var parts = uri.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
            vars[key] = value;
        });
        
        return vars;
    }

    init(){
        this.initRadioSectionLevel();
        this.initRadioSectionContentDisplay();
        this.initFilter();

            
        let params = this.getUrlVars();
        let anchors = params.id.split("#", 2);
        let sectionId = anchors[1] || this.getCookie('section') || 'section-0';  // if there is no sectionId defined then it displays the section-0
        
        /*if(sectionId === ''){
            let el = document.getElementById("navbarTogglerCourse");
            if((el !== null) && (el.firstChild !== null) && (el.firstChild.firstChild !== null)){
                sectionId = el.firstChild.firstChild.firstChild.getAttribute('data-section');
            }
        }*/

        this.goToSection(null, sectionId);

        this.pagination = document.getElementById('sectionPagination');

        this.ctrlPagination();
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
        let courseId = recit.utils.getQueryVariable("id");
        recit.http.WebApi.instance().setSectionLevel({courseId: courseId, sectionId: section.getAttribute("data-section-id"), level: radio.value}, callback);
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
        let courseId = recit.utils.getQueryVariable("id");
        recit.http.WebApi.instance().setSectionContentDisplay({courseId: courseId, sectionId: section.getAttribute("data-section-id"), value: radio.value}, callback);
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

        // cookies ctrl
        let options = this.filter.querySelectorAll("input");

        let cookies = [];
        for(let item of options){
            if(item.checked){
                cookies.push(item.value);
            }
        }

        recit.utils.setCookie('ttModeEditionFilter', cookies.join(","));
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

    goToSection(event, sectionId) {
        if(event !== null){
            event.preventDefault();
            sectionId = event.target.getAttribute('data-section');
        }
        
        if(sectionId.length === 0){
            return;
        }
        
        window.location.href = `${window.location.origin}${window.location.pathname}${window.location.search}#${sectionId}`;
        document.body.scrollTop = 0; // For Safari
        document.documentElement.scrollTop = 0; // For Chrome, Firefox, IE and Opera
        
        document.cookie = 'section=' + sectionId;
        /*$('.tt-section').css('display', 'none');
        $('.tt-imagebuttons').css('display', 'none');
        $(`#${sectionId}-imagebuttons`).css('display', 'grid');
        $(`#${sectionId}`).css('display', 'block');*/

        // hide all the sections
        let elems = document.getElementsByClassName('tt-section');
        for(let el of elems){
            el.style.display = 'none';
        }

        // look for the specific section and display it
        let el = document.getElementById(`${sectionId}`);
        if(el !== null){
            el.style.display = 'block';
            // if the section has subsections then display them too
            let children = el.getElementsByClassName('tt-section');
            for(let item of children){
                item.style.display = 'block';
            }

            // if the section has a parent section then display it too
            let grid = el.parentElement;
            if(grid !== null){
                let content = grid.parentElement;
                if(content !== null){
                    let section = content.parentElement;
                    if(section !== null){
                        section.style.display = "block";
                    }
                }
            }
        }

        // simulate the menu button click to hide the menu
        let btn = document.querySelector("[data-target='#navbarTogglerCourse']");
        if((btn !== null) && (btn.getAttribute('aria-expanded') === 'true')){
            btn.click();
        }

        this.ctrlPagination();
    }

    ctrlPagination(){
        if(this.pagination === null){ return; }

        let sections = document.getElementsByClassName('tt-section');

        let currentSection = this.getCookie('section');
        let btnPrevious = this.pagination.firstChild.firstChild;
        let btnNext = this.pagination.firstChild.lastChild;
        
        let iSection = 0;
        for(iSection = 0; iSection < sections.length; iSection++){
            if(sections[iSection].getAttribute('id') == currentSection){
                break;
            }
        }
      
        if(iSection <= 0){
            btnPrevious.classList.add("disabled");
        }
        else{
            btnPrevious.classList.remove("disabled");
            btnPrevious.firstChild.setAttribute('data-section', sections[iSection-1].getAttribute('id'));
        }
        
        if(iSection >= sections.length - 1){
            btnNext.classList.add("disabled");
        }
        else{
            btnNext.classList.remove("disabled");
            btnNext.firstChild.setAttribute('data-section', sections[iSection+1].getAttribute('id'));
        }

    }
}

// definition static attributes and methods to work with Firefox
M.recit.course.format.TreeTopics.messages = {
    error: "Une erreur inattendue est survenue. Veuillez réessayer."
}

// without jQuery (doesn't work in older IEs)
document.addEventListener('DOMContentLoaded', function(){ 
    M.recit.course.format.TreeTopics.instance = new M.recit.course.format.TreeTopics(); 
}, false);