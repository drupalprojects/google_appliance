(function($) {
  /**
   * Hit the /click service on the GSA.
   *
   * @param ct
   *   Click type.
   * @param url
   *   (optional) Target URL the user clicked on.
   * @param r
   *   (optional) Rank.
   * @param cd
   *   (optional) Click data.
   *
   * @see http://www.google.com/support/enterprise/static/gsa/docs/admin/70/gsa_doc_set/xml_reference/advanced_search_reporting.html#1080237
   */
  var click = function(ct, url, r, cd) {
    var esc  = encodeURIComponent,
        path = Drupal.settings.google_appliance.path,
        site = Drupal.settings.google_appliance.site,
        q    = Drupal.settings.google_appliance.q,
        s    = Drupal.settings.google_appliance.s,
        img,
        src;

    src = path +
      "?site=" + esc(site) +
      "&q="    + esc(q) +
      "&s="    + esc(s) +
      "&ct="   + esc(ct) +
      ((typeof url == "undefined" || url == null) ? "" : "&url=" + esc(url.replace(/#.*/, ""))) +
      ((typeof r   == "undefined" || r   == null) ? "" : "&r="   + esc(r)) +
      ((typeof cd  == "undefined" || cd  == null) ? "" : "&cd="  + esc(cd));

    img = document.createElement('img');
    img.src = src;
    return true;
  }

  /**
   * Make <a> tag aware of the /click service by hitting the GSA
   * with appropriate parameters when clicked.
   */
  $.fn.clickAware = function() {
    this.mousedown(function() {
      var link = $(this);
      click(
        (typeof link.data("ct") == "undefined") ? "OTHER" : link.data("ct"),
        this.href,
        (typeof link.data("r")  == "undefined") ? null    : link.data("r"),
        (typeof link.data("cd") == "undefined") ? null    : link.data("cd")
      );
    });
  }

  /**
   * Make <a> tags hit /click service when clicked.
   * Hit /click service with ctype 'load'.
   *
   * Supported click types:
   * - c        - search result.
   * - cluster  - cluster label on results page.
   * - keymatch - keymatch on results page.
   * - logo     - hyperlinked logo.
   * - nav.next - navigation, next page.
   * - nav.page - navigation, specific page.
   * - nav.prev - navigation, previous page.
   * - onebox   - onebox on results page.
   * - sort     - sort link on results page.
   * - spell    - spelling suggestion.
   * - synonym  - related query on results page.
   * - load     - load results page.
   *
   * @see https://www.google.com/support/enterprise/static/gsa/docs/admin/70/gsa_doc_set/admin_searchexp/ce_improving_search.html#1034719
   */
  $(document).ready(function() {
    $("a#logo").data("ct", "logo");
    $(".content .pager-item a").data("ct", "nav.page");
    $(".content .pager-next a, .content .pager-last a").data("ct", "nav.next");
    $(".content .pager-previous a, .content .pager-first a").data("ct", "nav.prev");
    $(".google-appliance-keymatch-results a").data("ct", "keymatch");
    $(".google-appliance-onebox-module a").data("ct", "onebox");
    $(".google-appliance-sorter a").data("ct", "sort");
    $(".google-appliance-synonym a").data("ct", "synonym");
    $(".google-appliance-spelling-suggestion a").data("ct", "spell");
    $(".google-appliance-result a").each(function(index) {
      $(this).data({"ct": "c", "r": index + 1});
    });
    $("#block-google-appliance-ga-related-searches .content a").each(function() {
      $(this).data({"ct": "cluster", "cd": this.innerHTML});
    });
    $("a").clickAware();
    click("load");
  });

})(jQuery);
