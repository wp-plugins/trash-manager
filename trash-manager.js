jQuery(document).ready( function($) {

	// show warnings for bulk actions
	$('#doaction, #doaction2').click(function(){
		var action = $('select[name="action"]').val(), action2 = $('select[name="action2"]').val();
		if ( action == 'delete' || action2 == 'delete' ) {
			var msg = trashMgrL10n.bulkDelete;
			if ( confirm(msg) ) {
				return true;
			}
			return false;
		}
		else if ( action == 'trash' || action2 == 'trash' ) {
			var msg = trashMgrL10n.bulkTrash;
			if ( confirm(msg) ) {
				return true;
			}
			return false;
		}
		else if ( action == 'untrash' || action2 == 'untrash' ) {
			var msg = trashMgrL10n.bulkUntrash;
			if ( confirm(msg) ) {
				return true;
			}
			return false;
		}
	});

	// show warnings for single comment actions
	$('#the-comment-list [class^=delete:the-comment-list]').click(function(event){
		var cl = $(this).attr('className'), msg = '';
		if ( cl.indexOf(':delete=1') != -1 )
			msg = trashMgrL10n.commentDelete;
		else if ( cl.indexOf(':trash=1') != -1 )
			msg = trashMgrL10n.commentTrash;
		else if ( cl.indexOf(':untrash=1') != -1 )
			msg = trashMgrL10n.commentUntrash;
		if ( msg == '' || confirm( msg ) )
			return true;
		event.stopImmediatePropagation();
		return false;
	});
});