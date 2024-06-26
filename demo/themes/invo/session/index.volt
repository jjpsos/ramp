<div class="row">
    <div class="col-md-6">
        <div class="page-header">
            <h2>Log In (Disabled)</h2>
        </div>

        <form action="/session/start" role="form" method="post">
            <fieldset>
                <div class="form-group">
                    {{ form.label('email', ['class': 'control-label']) }}
                    <div class="controls">
                        {{ form.render('email', ['class': 'form-control']) }}
                    </div>
                </div>
                <div class="form-group">
                    {{ form.label('password', ['class': 'control-label']) }}
                    <div class="controls">
                        {{ form.render('password', ['class': 'form-control']) }}
                    </div>
                </div>
                <div class="form-group">
                    {# submit_button('Login', 'class': 'btn btn-primary btn-large') #}
                </div>
            </fieldset>
        </form>
    </div>

    <div class="col-md-6">
        <div class="page-header">
            <h2>Don't have an account yet?</h2>
        </div>

        <p>Create an account offers the following advantages:</p>
        <ul>
            <li>Create, track and export your invoices online</li>
            <li>Gain critical insights into how your business is doing</li>
            <li>Stay informed about promotions and special packages</li>
        </ul>

        <div class="clearfix center">
            {# link_to('register', 'Sign Up', 'class': 'btn btn-primary btn-large btn-success') #}
        </div>
    </div>
</div>
