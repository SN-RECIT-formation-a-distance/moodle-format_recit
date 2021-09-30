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

    M.recit.course.format.TreeTopicsEditingMode.instance.init();
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
        this.loading = document.getElementById("tt-loading");
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
        let that = this;

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

                if(that.loading){
                    that.loading.style.display = "none";
                }
                
                callbackSuccess.call(this, result);
            }
        }

        xhr.onerror = this.onError;

        if(this.loading){
            this.loading.style.display = "block";
        }
        
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
        document.cookie = id + "=" + value + ";SameSite=Lax;" + expires;
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
        //this.onChangeFilter = this.onChangeFilter.bind(this);
        this.onChangeLevel = this.onChangeLevel.bind(this);
        this.onChangeContentDisplay = this.onChangeContentDisplay.bind(this);

        this.webApi = webApi;
        this.filter = null;

        this.init();
    }

    init(){
        this.initRadioSectionLevel();
        this.initRadioSectionContentDisplay();

        //this.initFilter();

        // Ouvrir la liste de sections automatiquement si la largeur de l'écran est plus grande que 1024
        if(window.screen.width > 1024){
            let navTabs = document.getElementById('navTabs');

            if(navTabs){
                navTabs.classList.add("show");
                document.querySelector('[data-target="#navTabs"]').classList.add("collapsed");
            }
        }
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

    /*initFilter(){
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
    }*/

    goToSection(event, sectionId){
        M.recit.course.format.TreeTopicsUtils.setCookie('section', sectionId);
    }

    onBtnShowHideHiddenActivities(event){
        let btn = event.currentTarget;
        let icon = btn.querySelector("i");
        let display = 'block';

        if(icon.classList.contains("fa-eye")){
            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");
            display = 'none';
        }
        else{
            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");
            display = 'block';
        }

        let elems = document.querySelectorAll("li .activity")
        for(let el of elems){
            let ret = el.querySelectorAll("div .availabilityinfo.ishidden");

            if(ret.length > 0){
                el.style.display = display;
            }
        }
    }

    onBtnShowHideCmList(event){
        let btn = event.currentTarget;
        let icon = btn.querySelector("i");
        let display = 'block';

        if(icon.classList.contains("fa-eye")){
            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");
            display = 'none';
        }
        else{
            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");
            display = 'block';
        }

        let elems = document.querySelectorAll("[data-course-section-cm-list='1']");
        for(let el of elems){
            el.style.display = display;
        }
    }
}

M.recit.course.format.TreeTopics = class{
    constructor(){
        this.getSectionContentResult = this.getSectionContentResult.bind(this);
        window.onscroll = this.onScroll.bind(this);

        this.webApi = new M.recit.course.format.TreeTopicsWebApi();

        this.sectionContent = null;
        this.pagination = null;
        this.menu = null;

        this.init();
    } 
    
    init(){
        let params = M.recit.course.format.TreeTopicsUtils.getUrlVars();

        // If there is no sectionId defined then it displays the section-0.
        let sectionId = params.sectionId || window.location.hash.substr(1, window.location.hash.length) || M.recit.course.format.TreeTopicsUtils.getCookie('section') || 'section-0';

        this.pagination = document.getElementById('sectionPagination');
        
        this.menu = document.getElementById("tt-recit-nav");
        
        this.sectionContent = document.getElementById("sectioncontent_placeholder");

        this.goToSection(null, sectionId);

        this.ctrlPagination();
    }

    onScroll(event){
        if(this.menu === null){ return; }

        let verticalMenu = this.menu.querySelector("[id='navbarTogglerCourse']");
        
        if((verticalMenu) && (this.menu.parentElement.classList.contains("vertical")) && (window.scrollY < 0)){
            verticalMenu.style.marginTop = `${window.scrollY}px`;
        }
    }

    ctrlMenu(sectionId){
        let menu, menuItem, menuItemDesc;

        if(this.menu === null){ return;}
        
        menu = this.menu;

        if(!menu.classList.contains('menuM1') && !menu.classList.contains('menuM3')){ return;}

        menuItemDesc = menu.querySelector(`[data-section=${sectionId}]`);

        if(menuItemDesc === null){ return; }
        
        menuItem = menuItemDesc.parentElement.parentElement;

        // Reset menu level 1 selection.
        this.resetMenuSelection();

        menuItem.setAttribute("data-selected", "1");

        // If the menu level1 item has a branch then it also select it.
        let branch = menu.querySelector(`[data-parent-section=${sectionId}]`);
        if(branch !== null){
            branch.setAttribute("data-selected", "1");
        }
         
        // Select menu level2 item.
        if((menuItem.parentElement.getAttribute("id") === "level2")){
            menuItem.parentElement.setAttribute("data-selected", "1");
            menuItem.parentElement.parentElement.setAttribute("data-selected", "1");
        }

        this.ctrlMenuResponsive(menu, menuItem, menuItemDesc, branch);
    }

    ctrlMenuResponsive(menu, menuItem, menuItemDesc, branch){
        let itemMenuResponsive = menu.querySelector('.btn-menu-responsive');
        let sectionTitle = itemMenuResponsive.children[1];
        let sectionSubtitle = itemMenuResponsive.children[2];

        if (sectionTitle){
            //Make appear the title of the section in the responsive menu
            sectionTitle.innerHTML = menuItemDesc.textContent;

            if(branch !== null){
                //Make appear the title of the sous section in the responsive menu
                let sections = branch.getElementsByClassName('menu-item');
                for(let sec of sections){
                    if(sec.getAttribute('data-selected') === "1"){
                        let subsection = sec.getElementsByClassName('menu-item-desc');
                        sectionSubtitle.innerHTML = subsection.textContent;
                        break;
                    }
                }
            }
        }
        this.ctrlOpeningMenuResponsive('closed');
    }

    //Open and close the menu responsive
    ctrlOpeningMenuResponsive(status){
        if(this.menu === null){ return; }
        this.menu.setAttribute('data-status', status);
    }

    //Open and close the submenu responsive
    ctrlOpeningSubMenuResponsive(event, sectionId){
        if(this.menu === null){ return; }

        let branch = this.menu.querySelector(`[data-parent-section=${sectionId}]`);
        if(branch !== null){
            if(branch.getAttribute("data-status") === "open"){
                branch.setAttribute("data-status", "closed");
                event.currentTarget.firstChild.classList.add("fa-plus");
                event.currentTarget.firstChild.classList.remove("fa-minus");
            }
            else{
                branch.setAttribute("data-status", "open");
                event.currentTarget.firstChild.classList.add("fa-minus");
                event.currentTarget.firstChild.classList.remove("fa-plus");
            }
        }
    }

    resetMenuSelection(){
        if(this.menu === null){ return;}

        let menu = this.menu;

        // Reset menu level 1 selection.
        let elems = menu.getElementsByClassName('menu-item');
        for(let el of elems){
            el.setAttribute("data-selected", "0");

            //set the negative(-) sign to plus(+) sign
            let levelSection = el.getElementsByClassName('menu-item-desc level-section active');
            if(levelSection.length >= 1){
                for(let item of levelSection){
                    let sectionIcon = el.getElementsByClassName('fas fa-minus');
                    for(let sec of sectionIcon){
                        item.classList.toggle("active");
                        sec.className = 'fas fa-plus';
                    }
                }
            }
        }

        // Reset menu level 2 selection.
        elems = menu.querySelectorAll('[data-parent-section]');
        for(let el of elems){
            el.setAttribute("data-selected", "0");
        }
    }

    getSectionContentResult(result){
        if(!result.success){
            alert(M.recit.course.format.TreeTopics.messages.error);
            return;
        }
        if(result.data === null){
            return;
        }        

        let doc = new DOMParser().parseFromString(result.data, "text/html");
        
        while (this.sectionContent.lastElementChild) {
            this.sectionContent.removeChild(this.sectionContent.lastElementChild);
        }
        
        this.preProcessingFilters(doc);

        window.scrollTo(0,0); 
        this.sectionContent.appendChild(doc.body.firstChild);

        this.postProcessingFilters(result.data);
    }

    preProcessingFilters(doc){
        let h5pobjects = doc.body.querySelectorAll('.h5p-iframe');
        for (let iframe of h5pobjects){
            iframe.onload = function(){
                iframe.style.height = iframe.contentWindow.document.documentElement.scrollHeight + 'px'; //adjust iframe to page height
            }
        }
    }

    postProcessingFilters(webApiResult){
        if(M.filter_mathjaxloader){
            M.filter_mathjaxloader.typeset();
        }

        this.loadMapLoaderPlugin(webApiResult);
    }

    loadMapLoaderPlugin(webApiResult){
        let match = webApiResult.match(/maploader\(\{(?:\s|.)*?\}\)/);

        if(match){ 
            let script = document.createElement("script");
            script.src = `${M.cfg.wwwroot}/mod/mapmodules/js/maploader.js`;
            window.document.head.appendChild(script);
            
            let loader = function(){
                if(typeof maploader === "undefined"){
                    setTimeout(loader, 500)
                    return;
                }

                let mapLoaderCode = match.pop();
                let f = new Function(mapLoaderCode);
                f();
            }
            loader();
        }
    }

    goToSection(event, sectionId) {
        sectionId = sectionId || '';
        if(event !== null){
            event.preventDefault();
            sectionId = event.currentTarget.getAttribute('data-section');

            // collapse the menu5 if it is the case
            if($){
                $(event.target.getAttribute("data-target")).collapse("hide");
            }
        }

        if(sectionId.length === 0){
            return;
        }

        this.ctrlMenu(sectionId);

        M.recit.course.format.TreeTopicsUtils.setCookie('section', sectionId);

        let params = M.recit.course.format.TreeTopicsUtils.getUrlVars();
        
        if(this.sectionContent !== null){
            this.webApi.getSectionContent({sectionid: sectionId, courseid: params.id}, this.getSectionContentResult);
        }        

        this.ctrlPagination();
    }

    ctrlPagination(){
        if(this.pagination === null){ return; }
        if(this.menu === null){ return; }

        let navbar = this.menu;
    
        let sections = navbar.querySelectorAll('[data-section]');
        if(sections === null){ return;}
        
        let currentSection = M.recit.course.format.TreeTopicsUtils.getCookie('section');
        let btnPrevious = this.pagination.firstChild.firstChild;
        let btnNext = this.pagination.firstChild.lastChild;
       
        let iSection = 0;
        for(iSection = 0; iSection < sections.length; iSection++){
            if(sections[iSection].getAttribute('data-section') === currentSection){
                break;
            }
        }

        if(!sections[iSection]){ return; }

        if(iSection <= 0){
            btnPrevious.classList.add("disabled");
        }
        else{
            btnPrevious.classList.remove("disabled");
            btnPrevious.firstChild.setAttribute('data-section', sections[iSection-1].getAttribute('data-section'));
        }

        if(iSection >= sections.length - 1){
            btnNext.classList.add("disabled");
        }
        else{
            btnNext.classList.remove("disabled");
            btnNext.firstChild.setAttribute('data-section', sections[iSection+1].getAttribute('data-section'));
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

    M.recit.course.format.TreeTopicsEditingMode.instance = new M.recit.course.format.TreeTopicsEditingMode(M.recit.course.format.TreeTopics.instance.webApi);
}, false);