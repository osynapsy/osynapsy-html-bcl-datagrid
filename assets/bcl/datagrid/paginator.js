BclPaginator = {
    init : function() {        
        Osynapsy.element('body').on('click','.bcl-paginator a', function(e){                
            e.preventDefault();
            let par = this.closest('.bcl-paginator');            
            let hdn = par.querySelector('input[type=hidden]:first-child');            
            hdn.value = this.dataset.value;
            console.log(hdn.value);
            Osynapsy.refreshComponents([par.dataset.parent]);
        });        
    }
};

if (window.Osynapsy) {
    Osynapsy.plugin.register('BclPager',function(){
        BclPaginator.init();        
    });
}
