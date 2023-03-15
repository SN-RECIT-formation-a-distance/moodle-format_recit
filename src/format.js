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

    moveModulesToSection(data, onSuccess){
        let options = {};
        options.data = data;
        options.service = "move_module_to_section";
        this.post(this.gateway, options, onSuccess);
    }

    deleteModules(data, onSuccess){
        let options = {};
        options.data = data;
        options.service = "delete_modules";
        this.post(this.gateway, options, onSuccess);
    }

    setModulesVisible(data, onSuccess){
        let options = {};
        options.data = data;
        options.service = "set_modules_visible";
        this.post(this.gateway, options, onSuccess);
    }

    deleteSection(data, onSuccess){
        let options = {};
        options.data = data;
        options.service = "delete_section";
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
        this.initMassActions();

        // Ouvrir la liste de sections automatiquement si la largeur de l'écran est plus grande que 1024
        if(window.screen.width > 1024){
            let navTabs = document.getElementById('navTabs');

            if(navTabs){
                navTabs.classList.add("show");
                document.querySelector('[data-target="#navTabs"]').classList.add("collapsed");
            }
        } 
    }

    initMassActions(){
        let items = document.querySelectorAll(".recitformat_massmove");

        for(let item of items){
            item.onchange = (event) => this.onChangeSection(event.target);
        }
        
        items = document.querySelectorAll(".recitformat_massmovesect");

        for(let item of items){
            item.onchange = (event) => document.location.href = event.target.value;
        }
        
        items = document.querySelectorAll(".recitformat_massdelete");

        for(let item of items){
            item.onclick = (event) => this.onDeleteModule(event.target);
        }
        
        items = document.querySelectorAll(".recitformat_masshide");

        for(let item of items){
            item.onclick = (event) => this.onChangeVisibility(event.target, 0);
        }
        
        items = document.querySelectorAll(".recitformat_massshow");

        for(let item of items){
            item.onclick = (event) => this.onChangeVisibility(event.target, 1);
        }
    }

    onChangeSection(combo){
        let items = document.querySelectorAll('.massactioncheckbox[data-section="'+combo.getAttribute('data-section')+'"]');
        let modules = [];
        for(let item of items){
            if (item.checked){
                modules.push(item.name)
            }
        }
        
        if (modules.length == 0){
            alert('Veuillez sélectionner des activités');
            return;
        }
        
        let callback = function(result){
            if(result.success){
                window.location.reload()
            }
            else{
                alert(M.recit.course.format.recit.messages.error);
            }
        }
        let courseId = this.getQueryVariable("id");
        this.webApi.moveModulesToSection({courseId: courseId, sectionId: combo.value, modules: modules}, callback);
    }

    onChangeVisibility(combo, isVisible){
        let items = document.querySelectorAll('.massactioncheckbox[data-section="'+combo.getAttribute('data-section')+'"]');
        let modules = [];
        for(let item of items){
            if (item.checked){
                modules.push(item.name)
            }
        }
        
        if (modules.length == 0){
            alert('Veuillez sélectionner des activités');
            return;
        }

        let callback = function(result){
            if(result.success){
                window.location.reload()
            }
            else{
                alert(M.recit.course.format.recit.messages.error);
            }
        }
        let courseId = this.getQueryVariable("id");
        this.webApi.setModulesVisible({courseId: courseId, isVisible: isVisible, modules: modules}, callback);
    }

    onDeleteModule(combo){
        if (!confirm('Êtes-vous sûr de vouloir supprimer ces activités?')){
            return;
        }
        let items = document.querySelectorAll('.massactioncheckbox[data-section="'+combo.getAttribute('data-section')+'"]');
        let modules = [];
        for(let item of items){
            if (item.checked){
                modules.push(item.name)
            }
        }
        if (modules.length == 0){
            alert('Veuillez sélectionner des activités');
            return;
        }
        let callback = function(result){
            if(result.success){
                window.location.reload()
            }
            else{
                alert(M.recit.course.format.recit.messages.error);
            }
        }
        let courseId = this.getQueryVariable("id");
        this.webApi.deleteModules({courseId: courseId, modules: modules}, callback);
    }


    getQueryVariable(name){
        let query = window.location.search.substring(1);
        let vars = query.split("&");
        for (let i = 0; i < vars.length; i++) {
            let pair = vars[i].split("=");
            if (pair[0] == name) {
                return pair[1];
            }
        }
        return false;
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

    deleteSection(section){
        if (!confirm('Êtes-vous sûr de vouloir supprimer cette section?')){
            return;
        }
        let callback = function(result){
            if(result.success){
                window.location.reload()
            }
            else{
                alert(M.recit.course.format.recit.messages.error);
            }
        }
        let courseId = this.getQueryVariable("id");
        this.webApi.deleteSection({courseId: courseId, sectionId: section}, callback);
    }

    goToSection(event, isMenu){
        if (event.target.hash){
            M.recit.theme.recit2.Utils.setCookieCurSection(event.target.hash);
            document.location.hash = event.target.hash;
        }

        
        if (isMenu){
            setTimeout(() => event.target.classList.remove('active'), 300);
            let el = document.querySelector('.nav-link.active');
            if (el){
                el.classList.remove('active');
            }
            let navlink = document.querySelector('.nav-link[aria-controls="'+event.target.getAttribute('aria-controls')+'"]');
            if (navlink){
                navlink.classList.add('active');
            }
        }
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

    expandAccordion(btn){
        let elems = document.querySelectorAll(".recitformatmenu .collapse");
        let hide = elems[0].classList.contains('show');
        btn.innerHTML = (hide ? '<i class="fa fa-arrow-right"></i> Tout déplier' : '<i class="fa fa-arrow-down"></i> Tout replier');
        
        for(let el of elems){
            if (hide){
                el.classList.remove('show');
            }else{
                el.classList.add('show');
            }
        }
        
        elems = document.querySelectorAll(".recitformatmenu .accordion-toggle");
        for(let el of elems){
            if (hide){
                el.classList.add('collapsed');
            }else{
                el.classList.remove('collapsed');
            }
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
        if (!this.sectionContent) return;
        this.lazyLoading = this.sectionContent.getAttribute('data-lazyloading') == '1';

        M.recit.theme.recit2.Ctrl.instance.sectionsNav.addOnSectionNavListener(this.goToSection);
        this.initMoodleFixes();

        if (this.lazyLoading){
            this.hideSections(true);
        }
    }

    initMoodleFixes(){
        require(['core_course/manual_completion_toggle'], toggle => {
            toggle.init()
        });
    }

    hideSections(showFirst){
        var els = document.querySelectorAll('.section');
        for (var el of els){
            el.style.display = 'none';
        }

        if (showFirst){
            var sectionId = document.querySelector('#menu-sections li[data-selected="1"] a')?.hash || '';
            var section = document.querySelector('[data-section="'+sectionId+'"]');
            if (section){
                section.style.display = 'block';
            }else{
                els[0].style.display = 'block';
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

    resizeH5P(iFrame){
        var H5P = iFrame.contentWindow.H5P;
        if (!H5P) {
            setTimeout(this.resizeH5P.bind(this), 500, iFrame);
            return;
        }

        if (iFrame.getAttribute('height')){
            iFrame.style.height = (parseInt(iFrame.getAttribute('height')) + 2) + 'px';
        }else{
            iFrame.style.height = iFrame.contentWindow.document.documentElement.scrollHeight + 'px'; //adjust iframe to page height
        }

        // Let h5p iframes know we're ready!
        var iframes = iFrame.contentWindow.document.getElementsByTagName('iframe');
        var ready = {
            context: 'h5p',
            action: 'ready'
        };
        for (var i = 0; i < iframes.length; i++) {
            if (iframes[i].src.indexOf('h5p') !== -1) {
                iframes[i].contentWindow.postMessage(ready, '*');
            }
        }
    }

    preProcessingFilters(doc){
        let h5pobjects = doc.querySelectorAll('.h5p-iframe,.h5p-player');
        let that = this;
        for (let iframe of h5pobjects){
            iframe.onload = function(){
                that.resizeH5P(iframe);
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

    goToSection(event) {
        event.preventDefault();

        let sectionId = event.target.hash || null;

        // collapse the menu5 if it is the case
        if($){
            if(event.target.hasAttribute("data-target")){
                $(event.target.getAttribute("data-target")).collapse("hide");
            }
        }

        if(sectionId === null){ return; }

        if (this.lazyLoading){
            let params = M.recit.theme.recit2.Utils.getUrlVars();
            if(this.sectionContent !== null){
                this.webApi.getSectionContent({sectionid: sectionId, courseid: params.id}, this.getSectionContentResult);
            }
        }else{
            var section = document.querySelector('[data-section="'+sectionId+'"]');
            if (section){
                this.hideSections();
                section.style.display = 'block';
                window.scrollTo(0,0);
            }
        }
    }
}

// Definition static attributes and methods to work with Firefox.
M.recit.course.format.recit.messages = {
    error: "Une erreur inattendue est survenue. Veuillez réessayer."
}

