var app = app || {};

(function ($) {
	'use strict';


	//var Registration = Backbone.Model.extend({
	//	defaults: {
	//		name: 'Register a new member'
	//	}
	//});
	//
	//var RegistrationView = Backbone.View.extend({
	//
	//	el: 'li',
	//
	//	render: function () {
	//		var html = '<a href="#" class="add-registrant">Register</a>';
	//		this.$el.html(html);
	//	}
	//});
	//
	//app.CoreView = Backbone.View.extend({
	//
	//	el: function () {
	//		return document.getElementById('registration');
	//	},
	//
	//	currentViews: {},
	//
	//	events: {
	//		'click .add-registrant': 'addRegistrant'
	//	},
	//
	//	initialize: function () {
	//		this.$posts = $('#posts');
	//	},
	//
	//	addRegistrant: function (e) {
	//		e.preventDefault();
	//		var regView = new RegistrationView({model: Registration});
	//		regView.render();
	//		this.$el.find('.reg-sidebar ul').append(regView.el);
	//	}
	//
	//});

	var regOptionsForm = function () {

		var SELF = this;

		SELF.init = function () {
			SELF.$form = $(document.getElementById('reg-options-form'));

			if (!SELF.$form.length) {
				return;
			}

			SELF.$form.find('[name=price-point]').on('change', SELF.showRestricted);
			SELF.$form.find('[name=member-type]').on('change', SELF.showRestricted);

			// if we are editing
			SELF.$form.find('[name=price-point]').trigger('change');

			SELF.$form.find('[name=price-point]').on('change', SELF.updatePricepoint);

		};

		SELF.updatePricepoint = function (e) {
			var $this = $(this),
				pricepoints = $this.data('pricepoints'), pricepoint;

			if (0 == $this.val()) {
				return SELF.hideMemberTypes();
			}

			for (var i = 0; i < pricepoints.length; ++i) {
				if ($this.val() == pricepoints[i]['id']) {
					break;
				}
			}

			if (i >= pricepoints.length || undefined == pricepoints[i]['member_types']) {
				return SELF.hideMemberTypes();
			}

			SELF.showMemberTypes(pricepoints[i]['member_types']);

		};

		SELF.hideMemberTypes = function () {
			SELF.$form.find('.member-type').hide()
				.find('option.type').remove();
		};

		SELF.showMemberTypes = function (types) {
			var $memberTypes = SELF.$form.find('.member-type');

			$memberTypes.find('option.type').remove();

			$(types).each(function () {
				$memberTypes.find('select').append('<option class="type" value="' + this.id + '">' + this.title + '</option>')
			});

			$memberTypes.show();
		};

		SELF.showRestricted = function(e) {
			var pricepoint = SELF.$form.find('[name=price-point]').val(),
				  membertype = SELF.$form.find('[name=member-type]').val();
			SELF.$form.find('.restricted').hide();

			SELF.$form.find('.restricted.' + pricepoint).show();
			SELF.$form.find('.restricted.' + membertype).show();
		};

		SELF.init();
	};

	$(document).ready(function () {
		new regOptionsForm();

		$('#registration form').on("keyup keypress", function(e) {
			var code = e.keyCode || e.which;
			if (code  == 13) {
				e.preventDefault();
				return false;
			}
		});

		$(".table-sort").stupidtable();

	});

})(jQuery);