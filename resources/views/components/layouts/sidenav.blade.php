<div id="mySidenav" class="sidenav">
    <div class="header">
        <div class="profile-picture" style=""></div>
        <div class="btn-group">
            <p class="dropdown-toggle" data-toggle="dropdown">
                {{Auth::user()->name}}
                <span class="caret">
            </p>
            <ul style="left:-50px;" class="dropdown-menu">
                <li>
                    <a href="/settings/profile" style="padding-top:9px;">Change profile picture</a>
                </li>
                <li>
                    <a href="/settings/categories" style="padding-top:9px;">Edit categories</a>
                </li>
                <li>
                    <a href="/settings/account" style="padding-top:9px;">Settings</a>
                </li>
                <li role="separator" class="divider"></li>
                <li>
                    <a href="/logout" style="padding-top:9px;">Logout</a>
                </li>
            </ul>
        </div>
    </div>
    <div class="divider"></div>
    <nav class="navigation">
        <ul>
            <li>
                <a href="/">Home</a>
            </li>
            <li>
                <a href="/profile/view/1">My Profile</a>
            </li>
            <li>
                <a href="/recipe/add">Add Recipe</a>
            </li>
            <li>
                <a href="/shopping/list">Shopping List</a>
            </li>
            <!-- <li>
                <a href="#">My Recipes</a>
            </li> -->
            <li>
                <a href="/admin/base/list">Admin</a>
            </li>
        </ul>
    </nav>
    <div class="divider"></div>
    <div class="personal">
        <h1>Latest 3 Favorites</h1>
        <div class="content">
            <ul>
                @foreach (\App\Models\Recipe::favourites()->forAuthUser()->limit(5)->get() as $recipe)
                    <li>
                        <a href="/recipe/{{$recipe->id}}">{{$recipe->name}}</a>
                    </li>
                @endforeach
                <li class="see-more">
                    <a href="/favorites">See more</a>
                </li>
            </ul>
            <div class="clear"></div>
        </div>
    </div>
    <div class="divider"></div>
    <div class="shared">
        <h1>Latest 3 Shared</h1>
        <div class="content">
            <ul>
                <li class="see-more">
                    <a href="/shared">See more</a>
                </li>
            </ul>
            <div class="clear"></div>
        </div>
    </div>

    <footer>
        <a class="link" target="_blank" href="https://cibatusrecipes.com/#contact">Contact</a>
        |
        <a class="link" target="_blank" href="https://cibatusrecipes.com/terms">Terms & Conditions</a>
        |
        <a class="link" target="_blank" href="https://cibatusrecipes.com/#faq">FaQ</a>
        <br>
        <p>&copy; Copyright 2017 -
            <a href="https://renedyhr.me" target="_blank">René Dyhr</a>
            &
            <a href="https://tbjean.me" target="_blank">Jeanette Pedersen</a>
        </p>
    </footer>
</div>