(function() {
    tinymce.create('tinymce.plugins.revostock_gallery', {
        init : function( ed, url ) {
           tinymce.plugins.revostock_gallery.theurl = url;
        },
        createControl : function(n, cm) {
            switch (n) {
                case 'revostock_gallery':
                    var c = cm.createSplitButton('revostock_gallery', {
                        title : 'Insert gallery of RevoStock media items',
                        image : tinymce.plugins.revostock_gallery.theurl + '/revostock_button_icon.png'
                    });

                    c.onRenderMenu.add(function(c, m) {
                      m.add({title : 'Insert Options', 'class' : 'mceMenuItemTitle'}).setDisabled(1);

                      m.add({title : 'Use default options', onclick : function() {
                            tinymce.activeEditor.selection.setContent('[revostock-gallery]');
                      }});

                      m.add({title : 'Insert particular file', onclick : function() {
                          tinymce.activeEditor.selection.setContent('[revostock-gallery file=12345]');
                      }});

                      m.add({title : 'Insert from mediabox', onclick : function() {
                          tinymce.activeEditor.selection.setContent('[revostock-gallery mediabox=12345]');
                      }});

                      m.add({title : 'Insert from specific producer', onclick : function() {
                          tinymce.activeEditor.selection.setContent('[revostock-gallery producer=12345]');
                      }});

                      m.add({title : 'Insert from specific content type', onclick : function() {
                          tinymce.activeEditor.selection.setContent('[revostock-gallery type=all|audio|video|aftereffects|motion]');
                      }});

                      m.add({title : 'Insert from specific search term(s)', onclick : function() {
                          tinymce.activeEditor.selection.setContent('[revostock-gallery search_terms=forest,moon]');
                      }});

                      m.add({title : 'Insert from group', onclick : function() {
                          tinymce.activeEditor.selection.setContent('[revostock-gallery group=newest|most_downloaded|editors_choice]');
                      }});

                      m.add({title : 'Limit the number of items', onclick : function() {
                          tinymce.activeEditor.selection.setContent('[revostock-gallery max_results=1-40]');
                      }});

                      m.add({title : 'Add custom CSS', onclick : function() {
                          tinymce.activeEditor.selection.setContent('[revostock-gallery css_prefix=yourprefix]');
                      }});
                    });


                    return c;
            }
        },
        getInfo : function() {
            return {
                longname    : "RevoStock Gallery Editor Button",
                author      : 'RevoStock',
                authorurl   : 'http://www.revostock.com/',
                infourl     : 'http://www.revostock.com/wordpress.html',
                version     : "0.9.15"
            };
        }
    });
    tinymce.PluginManager.add('revostock_gallery', tinymce.plugins.revostock_gallery);
})();