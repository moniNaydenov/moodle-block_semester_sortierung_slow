// This file is part of block_semsort for Moodle - http://moodle.org/
//
// It is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// It is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// If not, see <http://www.gnu.org/licenses/>.

/**
 * Javascript functions
 *
 * @package       block_semsort
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Simeon Naydenov (moniNaydenov@gmail.com)
 * @copyright     2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
YUI.add('moodle-block_semsort-semsort', function(Y){
var SEMSORT = function(config) {
    SEMSORT.superclass.constructor.apply(this, arguments);
};
SEMSORT.prototype = {
    /**
     * semsort id(block instance id)
     */
    id : null,
    /**
     * Initialise the tree object when its first created.
     */
    initializer : function(config) {
        this.id = config.id;
        var node = Y.one('#inst'+config.id);
        if (Y.one('.no_javascript')) {
            Y.one('.no_javascript').removeClass('no_javascript');
        }
        Y.all('#semesteroverviewcontainer fieldset .togglefavorites').set('href', 'javascript: void(0);');
        var self = this;
        Y.delegate('click', function(e){self.setNewStatus(e);}, node.one('#semesteroverviewcontainer'), 'fieldset legend');
        Y.delegate('click', function(e){self.toggleFavorites(e);}, node.one('#semesteroverviewcontainer'), 'fieldset .togglefavorites');
    },
    
    toggleFavorites : function(e) {
        var target = e.currentTarget.ancestor('fieldset.course');

        var cid = target.getData('id');

        var stat = '0';
        if (target.getData('fav') == '0') {
            target.one('.togglefavorites.on').removeClass('invisible');
            target.one('.togglefavorites.off').addClass('invisible');

            var newtarget = target.cloneNode(true);
            Y.one('#semesteroverviewcontainer fieldset.fav').insert(newtarget);
            makeCourseDraggable(newtarget, null);
            newtarget.setAttribute('data-fav', 1);
            newtarget.setAttribute('data-semester', 'fav');
            stat = '1';
            target.setAttribute('data-fav', 1);
            this.sortFavorites();
            Y.one('#semesteroverviewcontainer fieldset.fav').removeClass('empty');
            console.log(newtarget);
        } else {
            Y.one('#semesteroverviewcontainer fieldset.fav').all('fieldset.course').each(function(e) {
                    if (e.getData('id') == cid) {
                        e.remove();
                    }
                });
            Y.one('#semesteroverviewcontainer').all('fieldset.course').each(function(e) {
                    if (e.getData('id') == cid) {
                        e.one('.togglefavorites.off').removeClass('invisible');
                        e.one('.togglefavorites.on').addClass('invisible');
                        e.setAttribute('data-fav', '0');
                    }
                });
            var favcount = Y.one('#semesteroverviewcontainer fieldset.fav').all('fieldset.course')._nodes.length;
            if (favcount <= 0) {
                Y.one('#semesteroverviewcontainer fieldset.fav').addClass('empty');
            }
        }
        var params = {
            id: cid,
            status: stat
        };
        Y.io(M.cfg.wwwroot+'/blocks/semsort/ajax_favorites.php', {
            method:'GET',
            data:  build_querystring(params),
            context:this
        });
    },
    
    sortFavorites : function() {
        var favs = Y.one('#semesteroverviewcontainer fieldset.fav');
        var nothidden = favs.all('fieldset.course.nothidden')._nodes;
        var hidden = favs.all('fieldset.course.hidden')._nodes;
        nothidden.sort(this.customSorting);
        hidden.sort(this.customSorting);
        favs.all('fieldset.course').each(function(e){e.remove();});
        favs = Y.one('#semesteroverviewcontainer fieldset.fav .expandablebox');
        for (var i = 0; i < nothidden.length; i++) {
            favs.appendChild(nothidden[i]);
        }
        for (var i = 0; i < hidden.length; i++) {
            favs.appendChild(hidden[i]);
        }
    },
    
    customSorting : function(a, b) {
        var a = Y.one(a).one('legend .courselink').getHTML().toLowerCase();
        var b = Y.one(b).one('legend .courselink').getHTML().toLowerCase();
        return a < b ? -1 : b < a ? 1 : 0;
    },

    setNewStatus : function(e) {
        if (e.target.hasClass('courselink')) {
            return; //don't do anything when a link is pressed 
        }
        var fldset = e.currentTarget.ancestor();
        fldset.toggleClass('expanded');
        
        var btype = fldset.hasClass('semester') ? 's' : 'c';
        var bstate = fldset.hasClass('expanded') ? 1 : 0;
        var targetdiv = fldset.one('.expandablebox');
        var useajax = btype == 'c' && targetdiv.getHTML() == ''  && bstate == 1 ? 1 : 0;
        
        var params = {
            id: fldset.getData('id'),
            state: bstate,
            boxtype: btype,
            ajax: useajax
        };
        if (useajax) {
            fldset.addClass('loading');
        }
        Y.io(M.cfg.wwwroot+'/blocks/semsort/ajax_setstate.php', {
                method:'GET',
                data:  build_querystring(params),
                context:this,                
                on: {
                    complete: function(t, outcome) {
                        if (useajax == 1) {
                            fldset.removeClass('loading');
                            targetdiv.setHTML(outcome.responseText);
                        }
                    }
                }
            });
    }
};



var makeCourseDraggable = function(v, k) {
    var imagenode = v.one('legend span.move-drag-start');
    var nojslink = v.one('legend a.move-static');
    if (imagenode) {
        imagenode.removeClass('hidden');
        if (nojslink != null) {
            nojslink.remove();
        }

        var dd = new Y.DD.Drag({
            node: v,
            target: {
                padding: '0 0 0 20'
            }
        }).plug(Y.Plugin.DDProxy, {
                moveOnEnd: false
            }).plug(Y.Plugin.DDConstrained, {
                constrain2node: '#semesteroverviewcontainer',
                stickY: true
            });
        dd.addHandle('legend span.move-drag-start');
    }
};







//Static Vars
var goingUp = false, lastY = 0;

var list = Y.Node.all('#semesteroverviewcontainer .course');
list.each(makeCourseDraggable);

Y.DD.DDM.on('drag:start', function(e) {
    //Get our drag object
    var drag = e.target;
    //Set some styles here
    var html = '<div class="semester expanded">' + drag.get('node').get('outerHTML') + '</div>';

    drag.get('node').setStyle('opacity', '.25');
    drag.get('dragNode').addClass('block_semsort');
    drag.get('dragNode').set('innerHTML', html);
    drag.get('dragNode').setStyles({
        opacity: '.5',
        borderColor: drag.get('node').getStyle('borderColor'),
        backgroundColor: drag.get('node').getStyle('backgroundColor')
    });
});

Y.DD.DDM.on('drag:end', function(e) {
    var drag = e.target;
    //Put our styles back
    drag.get('node').setStyles({
        visibility: '',
        opacity: '1'
    });

     var params = {
     "block_semsort_move_course": drag.get('node').getData('id'),
     "block_semsort_move_target": drag.get('node').ancestor('.semester').all('.course').indexOf(drag.get('node')),
     "block_semsort_move_semester": drag.get('node').getData('semester')
     };

     Y.io(M.cfg.wwwroot+'/blocks/semsort/ajax_personalsort.php', {
     method:'GET',
     data:  build_querystring(params),
     context:this
     });


});

Y.DD.DDM.on('drag:drag', function(e) {
    //Get the last y point
    var y = e.target.lastXY[1];
    //is it greater than the lastY var?
    goingUp = (y < lastY);  //We are going up or down
    //Cache for next check
    lastY = y;
});

Y.DD.DDM.on('drop:over', function(e) {
    //Get a reference to our drag and drop nodes
    var drag = e.drag.get('node'),
        drop = e.drop.get('node');
    var dragSemester = drag.getData("semester");
    var dropSemester = drop.getData("semester");
    //Are we dropping on a li node?
    if (drop.hasClass('course') && dropSemester==dragSemester) {
        //Are we not going up?
        if (!goingUp) {
            drop = drop.get('nextSibling');
        }
        //Add the node to this list
        e.drop.get('node').get('parentNode').insertBefore(drag, drop);
        //Resize this nodes shim, so we can drop on it later.
        e.drop.sizeShim();
    }
});
/*
Y.DD.DDM.on('drag:drophit', function(e) {
    var drop = e.drop.get('node'),
        drag = e.drag.get('node');

    //if we are not on an li, we must have been dropped on a ul
    if (!drop.hasClass('coursebox')) {
        if (!drop.contains(drag)) {
            drop.appendChild(drag);
        }
    }
});

*/












// The tree extends the YUI base foundation.
Y.extend(SEMSORT, Y.Base, SEMSORT.prototype, {
    NAME : 'semsort-semsort',
    ATTRS : {
        instance : {
            value : null
        }
    }
});



/**
 * This namespace will contain all of the contents of the navigation_plus blocks
 * global navigation_plus and settings.
 * @namespace
 */
M.block_semsort = M.block_semsort || {
    /** The number of expandable branches in existence */
    instance : null,
    /**
     * Add new instance of navigation_plus tree to tree collection
     */
    init_add_semsort:function(properties) {
        /*if (M.core_dock) {
            M.core_dock.init(Y);
        }*/
        new SEMSORT(properties);
    }
};

}, '@VERSION@', {requires:['base', 'core_dock', 'io-base', 'node', 'node-base','dom', 'event-custom', 'event-delegate', 'json-parse', 'dd-constrain', 'dd-proxy', 'dd-drop', 'dd-plugin']});
