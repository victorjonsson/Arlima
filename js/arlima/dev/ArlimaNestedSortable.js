
/**
 * @param {ArlimaList} list
 */
function arlimaNestedSortable(list) {

    var $ = jQuery.noConflict(),
        $articles = list.$elem.find('.articles'),
        globalMaxDepth = 2, currentDepth = 0, originalDepth, minDepth, maxDepth,
        prev, next, prevBottom, nextThreshold, helperHeight, transport, isMovingWithChildren,
        isMovingCopy, itemStartIndex, startedOfAsChild, $clone, canBeChild, $parentArtElem,

    _updateCurrentDepth = function(ui, depth) {
        _updateDepthClass( ui.placeholder, depth, currentDepth );
        currentDepth = depth;
    },
    _depthToPx = function(depth) {
        return depth * 30;
    },
    _pxToDepth = function(px) {
        return Math.min(Math.floor(px / 30), 1); // Remove math min to allow deeper depths
    },
    _updateSharedVars = function(ui) {
        var depth;

        prev = ui.placeholder.prev();
        next = ui.placeholder.next();

        // Make sure we don't select the moving item.
        if( prev[0] == ui.item[0] ) prev = prev.prev();
        if( next[0] == ui.item[0] ) next = next.next();

        prevBottom = (prev.length) ? prev.offset().top + prev.height() : 0;
        nextThreshold = (next.length) ? next.offset().top + next.height() / 3 : 0;
        minDepth = (next.length) ? _itemDepth(next) : 0;

        if( prev.length )
            maxDepth = ( (depth = _itemDepth(prev) + 1) > globalMaxDepth ) ? globalMaxDepth : depth;
        else
            maxDepth = 0;
    },
    _nextIsChild = function(ui) {
        if (!next.length) _updateSharedVars(ui);
        return next.length && next[0].arlimaArticle.isChild();
    },
    _itemDepth = function($item) {
        var margin = $item.eq(0).css('margin-left');
        return _pxToDepth( margin && -1 != margin.indexOf('px') ? margin.slice(0, -2) : 0 );
    },
    _updateDepthClass = function($item, current, prev) {
        return $item.each(function(){
            var t = $(this);
            prev = prev || _itemDepth(t);
            $(this).removeClass('list-item-depth-'+ prev )
                .addClass('list-item-depth-'+ current );
        });
    },
    _shiftDepthClass = function($item,change) {
        return $item.each(function(){
            var t = $(this),
                depth = _itemDepth(t);
            $(this).removeClass('list-item-depth-'+ depth )
                .addClass('list-item-depth-'+ (depth + change) );
        });
    },
    _childlistItems = function($item) {
        var result = $();
        $item.each(function(){
            var t = $(this), depth = _itemDepth(t), next = t.next();
            while( next.length && _itemDepth(next) > depth ) {
                result = result.add( next );
                next = next.next();
            }
        });
        return result;
    },
    _getListId = function($item) {
        return $item.closest('.article-list').get(0).arlimaList.data.id;
    },
    _updateArticleListId = function($item){
        $item.get(0).arlimaArticle.listID = _getListId($item);
    },

    /**
     * Reset some of the sortable variables. This is needed because we can add draggables
     * to the list that doesn't have the same start-callback
     * @private
     */
    _resetSortableVars = function() {
        startedOfAsChild = false;
        itemStartIndex = -1;
    },

    /**
     * Remove the last article if the number of articles exceeds 50
     * @private
     */
    _reduceListToMaxSize = function() {
        if( list.size() > 50 ) {
            list.$elem.find('.article:last')[0].arlimaArticle.remove();
        }
    },

    /**
     * Called last when a dropped is successfully made
     * @param {jQuery} $elem
     * @param {Boolean} toggleUnsavedState
     * @private
     */
     _whenDropFinished = function($elem, toggleUnsavedState) {

        window.ArlimaUtils.log('Finished drop for '+list.data.id);

        // update parent props for child articles
        _reduceListToMaxSize();

        setTimeout(function() {
            // This must be done in a little while for DOM to catch up
            list.updateParentProperties();

            // update preview
            if( window.ArlimaArticleForm.isEditing($elem) ) {
                window.ArlimaArticleForm.setupForm(); // will also update preview if open
            } else if(
                window.ArlimaArticlePreview.isVisible() &&
                window.arlimaDragArticleParent &&
                window.ArlimaArticleForm.article.data.id == window.arlimaDragArticleParent
                ) {
                window.ArlimaArticlePreview.reload();
            }


        }, 200);

        window.ArlimaUtils.highlight($elem);

        if( toggleUnsavedState )
            list.toggleUnsavedState(true);

        list.$elem.trigger('change');
        list.$elem.css('z-index', 1);
        list.$elem.siblings().css('z-index', 1);

        // some opera bug fix?
        $elem[0].style.top = 0;

        _resetSortableVars();
    };


    $articles
        .sortable({
            items : '.article',
            placeholder: 'sortable-placeholder',
            connectWith: '.article-list:not(.imported) .articles',
            appendTo : 'body',
            helper: 'clone',
            distance: 10,
            zIndex: 9999,
            over : function(e, ui) {
                if( !ui.item.hasClass('ui-draggable') ) {
                    ui.item.data('sortableItem').scrollParent = ui.placeholder.parent();
                    ui.item.data('sortableItem').overflowOffset = ui.placeholder.parent().offset();
                }
            },
            start : function(e, ui) {

                if( ui.item.hasClass('ui-draggable')) return;

                //make sure you can scroll in the lists you hover over
                ui.item.data('sortableItem').scrollParent = ui.placeholder.parent();
                ui.item.data('sortableItem').overflowOffset = ui.placeholder.parent().offset();

                var height, width, parent, children, tempHolder;
                transport = ui.helper.children('.children-transport');
                window.arlimaMoveBetweenLists = false;

                // Set depths. currentDepth must be set before children are located.
                originalDepth = _itemDepth(ui.item);

                // The current position of the item in the list
                itemStartIndex = ui.item.prevAll().length;

                _updateCurrentDepth(ui, originalDepth);

                // Attach child elements to parent
                // Skip the placeholder
                startedOfAsChild = ui.item[0].arlimaArticle.isChild();
                parent = ( ui.item.next()[0] == ui.placeholder[0] ) ? ui.item.next() : ui.item;
                children = _childlistItems(parent);
                transport.append( children );
                isMovingWithChildren = children.length > 0;
                canBeChild = !isMovingWithChildren && ( !('arlimaArticle' in ui.item[0]) ||  ui.item[0].arlimaArticle.canBeChild() );
                window.arlimaDragArticleParent = false;

                if( ArlimaUtils.hasMetaKeyPressed(e) || list.data.isImported ) {
                    isMovingCopy = true;
                    $clone = ui.item.clone(false).show();
                    list.addArticle(new ArlimaArticle( $.extend(true, {}, ui.item.get(0).arlimaArticle.data), null, $clone, !list.data.isImported),false);
                    $clone.insertAfter(ui.item).removeClass('editing');
                    $(children.get().reverse()).each(function(index) {
                        var $child = $(this).clone(false);
                        list.addArticle(new ArlimaArticle( $.extend(true, {}, this.arlimaArticle.data), null, $child, !list.data.isImported), false);
                        $child.insertAfter($clone);
                    });
                } else {
                    isMovingCopy = false;
                    if( ui.item.get(0).arlimaArticle.isChild() ) {
                        window.arlimaDragArticleParent = ui.item.get(0).arlimaArticle.getParentArticle().data.id;
                    }
                }

                // Update the height of the placeholder to match the moving item.
                height = transport.outerHeight();
                // If there are children, account for distance between top of children and parent (and add 4 for margin)
                height += ( height > 0 ) ? (ui.placeholder.css('margin-top').slice(0, -2) * 1) + 4 : 0;
                height += ui.helper.outerHeight();
                helperHeight = height;
                height -= 2; // Subtract 2 for borders
                ui.placeholder.height(height);

                // Update the list of list items.
                tempHolder = ui.placeholder.next();
                tempHolder.css( 'margin-top', helperHeight + 'px' ); // Set the margin to absorb the placeholder
                ui.placeholder.detach(); // detach or jQuery UI will think the placeholder is a list item
                $(this).sortable( "refresh" ); // The children aren't sortable. We should let jQ UI know.
                ui.item.after( ui.placeholder ); // reattach the placeholder.
                tempHolder.css('margin-top', 0); // reset the margin

                // Now that the element is complete, we can update...
                _updateSharedVars(ui);
            },
            stop: function(e, ui) {

                var children,
                    isNowAChild,
                    depthChange = currentDepth - originalDepth,
                    listContainerElem = ui.item.closest('.article-list').get(0),
                    itemIndex = ui.item.prevAll().length;

                if( ui.item.hasClass('ui-draggable') ) {
                    // this item is taken care of in the recieve event
                    return;
                } else if( listContainerElem.arlimaList.data.id == list.data.id && list.data.isImported ) {
                    // we have moved an article inside an imported list (todo: prevent sorting inside the imported list )
                    e.preventDefault();
                    $clone.remove();
                    return false;
                }

                if ( ui.item[0].arlimaArticle.isDivider() && _nextIsChild(ui) ) {
                    $(this).sortable('cancel');
                    return false;
                }

                if( list.data.isImported ) {
                    ui.item[0].arlimaArticle.listID = listContainerElem.arlimaList.data.id;
                    ui.item[0].arlimaArticle.addClickEvents( !list.data.isImported );
                    if( ArlimaArticleForm.article && ArlimaArticleForm.article.opt('overridingURL') == ui.item[0].arlimaArticle.opt('overridingURL') ) {
                        // RE-edit article to show that its no longer blocked by the UI
                        ArlimaArticleForm.article = false;
                        ArlimaArticleForm.edit(ui.item[0].arlimaArticle);
                        if( ArlimaArticleForm.article.data.image && ArlimaArticleForm.article.data.image.url ) {
                            // Sideload image
                            window.ArlimaImageUploader.showPreloader();
                            window.ArlimaBackend.saveExternalImage(
                                ui.item[0].arlimaArticle.data.image.url,
                                '',
                                function(json) {
                                    window.ArlimaImageUploader.removeNotice(); // remove preloader
                                    if( json ) {
                                        window.ArlimaImageManager.setNewImage(
                                            json.url,
                                            json.attachment,
                                            false,
                                            ArlimaArticleForm.article.data.image.size,
                                            ArlimaArticleForm.article.data.image.alignment
                                        );
                                    }
                                }
                            );
                        }
                    }
                }

                // Return child elements to the list
                children = transport.children().insertAfter(ui.item);

                if(children.length > 0) {
                    children.each(function(){
                        _updateArticleListId($(this));
                        //the click event is lost, for some reason
                        this.arlimaArticle.addClickEvents( !list.data.isImported );
                    });
                }

                // Update depth classes
                if( depthChange !== 0 ) {
                    _updateDepthClass( ui.item, currentDepth );
                    _shiftDepthClass( children, depthChange );
                }

                // update parent props for child articles
                list.updateParentProperties();
                isNowAChild = ui.item[0].arlimaArticle.isChild();

                if( listContainerElem && (itemIndex != itemStartIndex || isNowAChild != startedOfAsChild) ) {
                    // this item does not come from the search
                    listContainerElem.arlimaList.toggleUnsavedState(true);
                }

                if( !isMovingCopy && (!listContainerElem || listContainerElem.arlimaList.data.id != list.data.id)) {
                    list.toggleUnsavedState(true);
                }

                if( !window.arlimaMoveBetweenLists )
                    _whenDropFinished(ui.item, false);
                else {

                    // This may be redundant if we moved a copy to another list... but nevermind...
                    list.updateParentProperties();

                    _resetSortableVars();
                }
            },
            sort: function(e, ui) {

                var offset = ui.helper.offset(),
                    edge = offset.left,
                    depth = _pxToDepth( edge - ui.placeholder.parent().offset().left );

                if( isMovingWithChildren ) {
                    depth = 0; // Don't allow article to go deeper if it has children
                }

                // Make sure the placeholder is inside the list.
                // Otherwise fix it, or we're in trouble.
                if( ! ui.placeholder.parent().hasClass('articles') )
                    (prev.length) ? prev.after( ui.placeholder ) : $articles.prepend( ui.placeholder );

                _updateSharedVars(ui);
                
                // Check if divider is inside a group, if so add
                // red background to helper object
                if ( ui.item[0].arlimaArticle && ui.item[0].arlimaArticle.isDivider() ) {
                    ui.placeholder.css('background-color', _nextIsChild(ui) ? 'red' : '');
                }

                if( (prev.length && !prev[0].arlimaArticle.isChild() && !prev[0].arlimaArticle.canHaveChildren()) || !canBeChild ) {
                    depth = 0; // We cant go deeper if parent article for some reason don't accept children
                }

                // Check and correct if depth is not within range.
                if ( depth > maxDepth ) depth = maxDepth;
                else if ( depth < minDepth ) depth = minDepth;

                if( depth != currentDepth && !isMovingWithChildren && canBeChild )
                    _updateCurrentDepth(ui, depth);

                // If we overlap the next element, manually shift downwards
                if( nextThreshold && offset.top + helperHeight > nextThreshold ) {
                    next.after( ui.placeholder );
                    _updateSharedVars( ui );
                    $(this).sortable( "refreshPositions" );
                }
            },
            receive: function(event, ui) {
                var $addedElement;
                if( ui.sender.hasClass('ui-draggable') ) {
                    var article =  new ArlimaArticle($.extend(true, {}, ui.item.context.arlimaArticle.data), list.data.id, false, !list.data.isImported);
                    article.$elem.insertAfter(list.$elem.find('ui-draggable'));
                    article.$elem.addClass('list-item-depth-' + currentDepth).insertAfter($('.ui-draggable', this));
                    list.$elem.find('.ui-draggable').remove();
                    $addedElement = article.$elem;
                } else {
                    $addedElement = ui.item;
                    ui.item[0].arlimaArticle.listID = list.data.id;
                    // Re-bind click events
                    ui.item[0].arlimaArticle.addClickEvents(true);
                }

                _whenDropFinished($addedElement, true);

                window.arlimaMoveBetweenLists = true; // prevent that some things gets called twice due to the stop event
            }
        });
}