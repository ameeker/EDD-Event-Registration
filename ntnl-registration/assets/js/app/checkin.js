(function($) {
	'use strict';

	var NTNLCheckin = function($button) {
		var SELF = this;

		SELF.data = {};

		SELF.init = function() {
			SELF.$button = $button;

			if ( ! SELF.$button.length ){
				return;
			}

			SELF.$button.on('click', SELF.saveAttendee);
		};


		SELF.saveAttendee = function(e) {
			e.preventDefault();

			if ( SELF.$button.hasClass('disabled') ) {
				return;
			}

			var attendeeID = SELF.$button.attr('data-attendee');

			SELF.$button.val('Saving');
			SELF.$button.addClass('disabled');
			SELF.$button.attr('disabled', 'disabled');

			SELF.data['attendee']   = attendeeID;
			SELF.data['device_id']  = $('input[name=device-id-' + attendeeID + ']').val();
			SELF.data['checked_in'] = $('input[name=checked-in-' + attendeeID + ']').attr('checked');
			SELF.data['security']   = ntnlrCheckin.security;
			SELF.data['conference'] = ntnlrCheckin.conference;


			wp.ajax.send( 'ntnlr_checkin_attendee', {
				data : SELF.data,
				success : SELF.success,
				error : SELF.error
			});
		};

		SELF.success = function() {
			SELF.$button.val('Save');
			SELF.$button.removeClass('disabled');
			SELF.$button.attr('disabled', false);
		};

		SELF.error = function(error) {
			SELF.$button.val('Save');
			SELF.$button.removeClass('disabled');
			SELF.$button.attr('disabled', false);

			SELF.$button.after('<br /> Something went wrong, please refresh the page and try again.' );
		};

		SELF.init();
	};


	$(document).ready( function(){
		$('.attendee-save').each( function() {
			new NTNLCheckin($(this));
		});
	});

})(jQuery);