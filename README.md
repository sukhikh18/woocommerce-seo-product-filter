## Wordpress taxanomy filter

### How to use :
1. add start and end sidebar actions (for include form)
    ```php
    do_action("before_sidebar");
        dynamic_sidebar( "archive");
    do_action("after_sidebar");
    ```

2. add widgets in /wp-admin/widgets.php
    - one or more "filter" widgets and submit type filter

3.  All done! Enjoy

todo (in future, soon):
- give choose relation
- can choose tax or meta
- add filter type
- add range data type
- add can use ajax

issues:
- can't update taxanomy. (Only create) @done (17/04/10)
