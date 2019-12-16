<ul class="nav nav-pills nav-stacked nav-bracket">

    <?php

    foreach ($nav as $group)
    {

        /*
         * Open group
         */
        if (count(get_object_vars($group->items)) > 1)
        {

            $active_group_style          = isset($group->active) ? ' nav-active' : '';
            $active_group_children_style = isset($group->active) ? ' style="display: block;"' : '';

            ?>

            <li class="nav-parent{{ $active_group_style }}">
                <a href="javascript:void(0);">
                    <i class="fa {{ $group->icon }}"></i>
                    <span>{{{ $group->name }}}</span></a>
                    <ul class="children"{{ $active_group_children_style }}>

            <?php

        }

        /*
         * Individual items
         */
        foreach ($group->items as $item)
        {

            $active_item_style = isset($item->active) ? ' active' : '';

            ?>

            <li class="{{ $active_item_style }}">
                <a href="{{ $item->target }}">
                    <i class="fa {{ $item->icon }}"></i>
                    <span>{{{ $item->name }}}</span>
                </a>
            </li>

            <?php

        }

        /*
         * Close group
         */
        if (count(get_object_vars($group->items)) > 1)
        {

            ?>

                </ul>
            </li>

            <?php

        }

    }

    ?>

</ul>