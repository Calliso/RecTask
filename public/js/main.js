$(()=>{
    $(()=>{
        $('#currencySubmit').on('click',(e)=>{
            e.preventDefault();
            $.post('/', $('#formCurrency').serialize(), (data, x, xhr)=>{
                console.log(xhr.status);
                if(xhr.status == 200){
                    drawTable(JSON.parse(data));
                }
            });
        });
    })
})


function drawTable(data){
    $('#table tbody').html('');
    $('#table').css('display', 'block');
    let html = '';
    console.log(data);
    for(let currency in data.now.rates){
        html+= "<tr>";
        html+= "<th scope=\"row\">" + currency + "</th>";
        html+= "<td>" + data.now.rates[currency] + "</td>";
        html+= "<td>" + data.selectedDate.rates[currency] + "</td>";
        html+= "<td>" + data.difference[currency] + "%</td>";
        html+= "</tr>";
    }
    $('#table tbody').html(html);

}