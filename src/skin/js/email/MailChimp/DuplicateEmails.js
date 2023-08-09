//
//  This code is under copyright not under MIT Licence
//  copyright   : 2018 Philippe Logel all right reserved.
//                This code can't be included in another software.
//
//  Updated : 2020/06/18
//


$(document).ready(function () {

  window.CRM.dataFundTable = $("#duplicateTable").DataTable({
    ajax:{
      url: window.CRM.root + "/api/persons/duplicate/emails",
      type: 'GET',
      contentType: "application/json",
      dataSrc: "emails"
    },
    "language": {
      "url": window.CRM.plugin.dataTable.language.url
    },
    columns: [
      {
        width: 'auto',
        title:i18next.t('Email'),
        data:'email',
        render: function(data, type, full, meta) {
          return data;
        }
      },
      {
        width: 'auto',
        title:i18next.t('People'),
        data:'people',
        render: function(data, type, full, meta) {
          var render ="<ul>";
          $.each( data, function( key, value ) {
              render += "<li><a href='"+ window.CRM.root + "/v2/people/person/view/" +value.id + "' target='user' />"+ value.name + "</a></li>";
          });
          render += "</ul>"
          return render;
        }
      },
      {
        width: 'auto',
        title:i18next.t('Families'),
        data:'families',
        render: function(data, type, full, meta) {
          var render ="<ul>";
          $.each( data, function( key, value ) {
              render += "<li><a href='"+ window.CRM.root + "/v2/people/family/view/" +value.id + "' target='family' />"+ value.name + "</a></li>";
          });
          render += "</ul>"
          return render;
        }
      },
    ],
    responsive: true,
    createdRow : function (row,data,index) {
      $(row).addClass("duplicateRow");
    }
  });
});
