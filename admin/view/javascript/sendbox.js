$(document).ready(() => {
  $("input:radio[name='rate']").change((e) => {
    console.log(e.target);
    console.dir(e.target);
    const courierPrice = e.target.dataset.courierPrice;
    let feeTag = $("#fee")[0];
    feeTag.innerText = ((courierPrice !== null && courierPrice !== undefined) ? "Fee: ₦"+ courierPrice : feeTag.innerText = "Fee: N0.00");    

  })
 /*  $("#rates").change((e) => {
    const courierPrice = e.target.selectedOptions[0].dataset.courierPrice;
    let feeTag = $("#fee")[0];
    feeTag.innerText = ((courierPrice !== null && courierPrice !== undefined) ? "Fee: ₦"+ courierPrice : feeTag.innerText = "Fee: N0.00");    
  }) */
  $("#shipment-form").submit((e) => {
    const selectedOption = $("#rates").find("input:checked")[0];
    if (!selectedOption){
      alert("Select courier");
      e.preventDefault();
      return;

    }
    const courierPrice = selectedOption.dataset.courierPrice;
    console.log(courierPrice)
    console.log(selectedOption)

    if (courierPrice === null || courierPrice === undefined){
      alert("Select courier") 
      e.preventDefault();
      return;
    }
    $("#ship-btn").prop('disabled', true)
  });
  //   console.log('clicked')
  //   const selected_courier_id = selectedOption.value
  //   console.log(selected_courier_id)
   /*  $.ajax({
      type: 'post',
      data: {selected_courier_id: selected_courier_id},
      success: function(response){
       const res = ((response.includes('insufficient balance')) ? "insufficient funds login to your sendbox account and top up your wallet" : "Success");
       console.log(response);
       console.log('sometext');
       alert(res);
       $("#ship-btn").prop('disabled', false);
      }
     }); */
        
  // })

});
