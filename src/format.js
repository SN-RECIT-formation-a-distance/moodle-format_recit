// Javascript functions for RÉCIT course format.

M.course = M.course || {};
M.course.format = M.course.format || {};

/**
 * Get sections config for this format
 *
 * The section structure is:
 * <ul class="formatrecit">
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
        container_class : 'formatrecit',
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

    M.recit.course.format.recit.EditingMode.instance.init();
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
M.recit.course.format.recit = M.recit.course.format.recit || {};
M.recit.course.format.recit.WebApi = class{
    constructor(){
        this.gateway = this.getGateway();

        this.post = this.post.bind(this);
        this.onError = this.onError.bind(this);
        this.loading = document.getElementById("tt-loading");
    }

    getGateway(){
        return `${M.cfg.wwwroot}/course/format/recit/Gateway.php`;
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

    getSectionContent(data, onSuccess){
        let options = {};
        options.data = data;
        options.service = "get_section_content";
        this.post(this.gateway, options, onSuccess);
    }
}

M.recit.course.format.recit.EditingMode = class{
    constructor(webApi){
        this.onChangeLevel = this.onChangeLevel.bind(this);

        this.webApi = webApi;
        this.filter = null;

        this.init();
    }

    init(){
        this.initRadioSectionLevel();

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
                alert(M.recit.course.format.recit.messages.error);
            }
        }
        let courseId = this.getQueryVariable("id");
        this.webApi.setSectionLevel({courseId: courseId, sectionId: section.getAttribute("data-section-id"), level: radio.value}, callback);
    }

    goToSection(event, sectionId){
        M.recit.theme.recit2.Utils.setCurrentSection(sectionId);
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

M.recit.course.format.recit.NonEditingMode = class{
    constructor(){
        this.getSectionContentResult = this.getSectionContentResult.bind(this);
        this.goToSection = this.goToSection.bind(this);
        this.webApi = new M.recit.course.format.recit.WebApi();
        this.sectionContent = null;

        this.init();
    } 
    
    init(){
        this.sectionContent = document.getElementById("sectioncontent_placeholder");

        let menu = document.getElementById("nav-sections");
        if(menu){
            let sections = menu.querySelectorAll('a');

            for(let section of sections){
                section.addEventListener("click", this.goToSection);
            }
        }

        let pagination = document.getElementById("sectionPagination");
        if(pagination){
            let buttons = pagination.querySelectorAll('a');

            for(let btn of buttons){
                btn.addEventListener("click", this.goToSection);
            }
        }
    }

    getSectionContentResult(result){
        if(!result.success){
            alert(M.recit.course.format.recit.messages.error);
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
            sectionId = event.target.hash || "";

            // collapse the menu5 if it is the case
            if($){
                if(event.target.hasAttribute("data-target")){
                    $(event.target.getAttribute("data-target")).collapse("hide");
                }
            }
        }

        if(sectionId.length === 0){ return; }

        let params = M.recit.theme.recit2.Utils.getUrlVars();
        if(this.sectionContent !== null){
            this.webApi.getSectionContent({sectionid: sectionId, courseid: params.id}, this.getSectionContentResult);
        }        
    }
}

// Definition static attributes and methods to work with Firefox.
M.recit.course.format.recit.messages = {
    error: "Une erreur inattendue est survenue. Veuillez réessayer."
}

// Without jQuery (doesn't work in older IEs).
document.addEventListener('DOMContentLoaded', function() {
    M.recit.course.format.recit.NonEditingMode.instance = new M.recit.course.format.recit.NonEditingMode();

    M.recit.course.format.recit.EditingMode.instance = new M.recit.course.format.recit.EditingMode(M.recit.course.format.recit.NonEditingMode.instance.webApi);
}, false);