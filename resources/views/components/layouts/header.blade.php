<div class="header">
    <div class="menu">
        <a href="javascript:void(0)" id="closenav" style="display:none;" onclick="closeNav()">
        <i class="fa fa-bars"></i>
        </a>
        <a href="javascript:void(0)" id="opennav" onclick="openNav()">
        <i class="fa fa-bars"></i>
        </a>
    </div>
    <a href="/" class="logo">
    <img src="/images/logo.png">
    </a>
    <div class="categories">
        <div class="center">
            @foreach (\App\Models\Category::with('icon')->forAuthUser()->get() as $category)
                <div class="list-group-item" data-toggle="tooltip" data-placement="top"
                title="{{$category->name}}">
                    <a href="/category/{{$category->slug}}">
                    <i class="icon icon-{{$category->icon->class}}"></i>
                    </a>
                </div>
            @endforeach
            <div class="btn-group" style="margin-left:30px;">
                <i class="icon-loop dropdown-toggle" data-toggle="dropdown"></i>
                <div class="dropdown-menu" style="right:34px;left:inherit;top:-10px;padding:5px;">
                    <form method="get" action="/recipe/search">
                        <div class="input-group" style="margin:0;">
                            <input class="form-control" name="q" value="" placeholder="Search...">
                            <span class="input-group-btn">
                            <button class="btn btn-default" type="submit">
                            <i class="fa fa-search"></i>
                            </button>
                            </span>
                        </div>
                    </form>
                </div>
            </div>
            <div class="clear"></div>
        </div>
    </div>
</div>