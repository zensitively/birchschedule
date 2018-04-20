(function($) {
  var ns = birchpress.namespace('birchschedule.ppayment.bookingform', {
    __init__: function() {
      birchpress.addFilter('birchschedule.view.bookingform.bookSucceed', function(fns, message) {
        fns['prepayment'] = function(message) {
          $('.birs_error').hide("");
          $('#birs_booking_box').hide();
          $('#birs_booking_success').html(message);
          $('#birs_booking_success').show("slow", function() {
            birchpress.util.scrollTo(
              $("#birs_booking_success"),
              600, -40);
          });
        }
        return fns;
      });
    },

    initPlaceOrderForm: function() {
      $('#birs_place_order').click(function() {
        var funcs = ns.getPlaceOrderFuncs();
        var method = $('input:radio[name=birs_payment_method]:checked').val();
        if (_.has(funcs, method)) {
          var placeOrder = funcs[method];
          placeOrder();
        }
      });
    },

    getPlaceOrderFuncs: function() {
      return {
        paylater: function() {
          var ajaxUrl = birchschedule.model.getAjaxUrl();
          var postData = $.param({
            action: 'birchschedule_ppayment_confirm_paylater',
            appointment1on1_id: $('#birs_appointment1on1_id').val()
          });
          $('#birs_please_wait').show("slow");
          $.post(ajaxUrl, postData, function(data, status, xhr) {
            $('#birs_please_wait').hide("slow");
            var result = birchschedule.model.parseAjaxResponse(data);
            if (result.errors) {
              birchschedule.view.showFormErrors(result.errors);
            } else if (result.success) {
              var bookSucceed = birchschedule.view.bookingform.bookSucceed();
              birchschedule.view.bookingform.bookingSucceed(bookSucceed[result.success.code], result.success.message);
            }
          });
        }
      };
    }
  });
})(jQuery);