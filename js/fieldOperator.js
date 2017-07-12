var counter = {
    count : 1,
    set : function(val) { this.count=val; },
    get : function() { return this.count; },
    plus: function() { this.count+=1; }
}
var $=jQuery.noConflict();
$(document).ready(function(){
    $("button#add_data").on("click",()=>{
        counter.plus();
        let count=counter.get();
        let template=`
            <fieldset>
                <legend>Grupa ${count}</legend>
                <label for="province">Województwo*</label>
                <select class="province" id="province_${count}" data-id="${count}">
                    <option>Pusto</option>
                </select>
                <label for="community">Gmina*</label>
                <select class="community" id="community_${count}">
                    <option>Pusto</option>
                </select>
                <input class="prov" type="hidden" name="province_pk[${count}][province_pk]" value="" data-id="${count}">
                <input class="comm" type="hidden" name="community_pk[${count}][community_pk]" value="" data-id="${count}">
                <button type="button" class="remove" onclick="jQuery(this).parent().remove()">Usuń</button>
            </fieldset>
        `;
        
        $("#here_put").append(template);
        loadProvince(count);
        setupChangeProvince(count);
    });
})

function setupChangeProvince(id_province) {
    $(`select#province_${id_province}`).on("change",()=>{ 
        loadCommunity(id_province);
        let val_comm= $(`select#community_${id_province} option`).first().val(); //this.value;
        setCommHiddenVal(id_province, val_comm);
    });

    //id_province==id_comm
    $(`select#community_${id_province}`).on("change",()=>{ 
        let val_comm= $(`select#community_${id_province} option:selected`).val(); //this.value;
        setCommHiddenVal(id_province, val_comm);
    });
}

function setProvinceHiddenVal(province_id, province_val) {
    $(`.prov[data-id=${province_id}]`).val(province_val);
}

function setCommHiddenVal(comm_id, comm_val) {
    $(`.comm[data-id=${comm_id}]`).val(comm_val);
}

function loadProvince(id_province=0,active=-1,callback=undefined) {
    $.post("http://katastr.agridata.eu/AJAX/",{rq:0}, function(data){
        data=JSON.parse(data);
        $(`select#province_${id_province} option`).remove();
        for(let elem in data) {
            $(`select#province_${id_province}`).append(`<option value=${data[elem].pk}>${data[elem].name}</option>`);
        }
        if($(`select#province_${id_province}`).length > 0)
            loadCommunity(id_province,active,callback);
    });
};

function loadCommunity(id_province,active_province=-1,callback=undefined) {
    let firstProvince=active_province==-1 ? $(`select#province_${id_province} option:selected`).val() : active_province;
    let selectComm=$(`select#community_${id_province}`);
    selectComm.html("");
    setProvinceHiddenVal(id_province,firstProvince);

    $.post("http://katastr.agridata.eu/AJAX/",{rq:1,state:firstProvince}, function(data){
        data=JSON.parse(data);
        for(let elem in data)
            selectComm.append(`<option value=${data[elem].pk}>${data[elem].name}</option>`);
    
            
    }).done(function(){
       if(callback!=undefined) callback();
    });
}

function selectData(id_province,province_val, id_comm,comm_val) {
    loadProvince(id_province,province_val,function(){
        $(`select#province_${id_province}`).val(province_val);
        setProvinceHiddenVal(id_province,province_val)
        $(`select#community_${id_comm}`).val(comm_val);
        setCommHiddenVal(id_comm,comm_val);
        setupChangeProvince(id_province);
    });
    
}
