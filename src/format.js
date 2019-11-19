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

        this.init();
    }

    init(){
        this.initRadioSectionLevel();
        this.initFilter();
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
                alert("Une erreur inattendue est survenue. Veuillez réessayer.");
            }
        }
        let courseId = recit.utils.getQueryVariable("id");
        recit.http.WebApi.instance().setSectionLevel({courseId: courseId, sectionId: section.getAttribute("data-section-id"), level: radio.value}, callback);
    }

    initFilter(){
        let filter = document.getElementById("ttModeEditionFilter");
        
        if(filter === null){ return; }
        
        let options = filter.querySelectorAll("input");

        for(let item of options){
            item.onchange = this.onChangeFilter;
        }
    }

    onChangeFilter(event){
        switch(event.target.name){
            case "act": this.displayActivities(event.target.checked); break;
            case "sum": this.displaySummary(event.target.checked); break;
        }
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

// without jQuery (doesn't work in older IEs)
document.addEventListener('DOMContentLoaded', function(){ 
    M.recit.course.format.TreeTopics.instance = new M.recit.course.format.TreeTopics(); 
}, false);
