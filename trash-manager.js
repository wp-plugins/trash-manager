// Trash Manager JavaScript, version 1.1

jQuery(document).ready( function($) {

	// show warnings for bulk actions
	$('#doaction, #doaction2').click(function(){
		var action = $('select[name="action"]').val(), action2 = $('select[name="action2"]').val(), msg = '';
		if ( action == 'delete' || action2 == 'delete' )
			msg = trashMgrL10n.bulkDelete;
		else if ( action == 'trash' || action2 == 'trash' )
			msg = trashMgrL10n.bulkTrash;
		else if ( action == 'untrash' || action2 == 'untrash' )
			msg = trashMgrL10n.bulkUntrash;
		if ( msg != '' && !confirm( msg ) )
			return false;
		return true;
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