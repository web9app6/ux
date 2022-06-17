import React from 'react';

export default function (props) {
    if (props.packages.length === 0) {
        return (
            <div className="alert alert-info">Sad trombone... we haven't built any components that match this search yet!</div>
        );
    }

    return (
        <div className="row">
            {props.packages.map(item => (
                <a
                    key={item.name}
                    href={item.url}
                    className="col-12 col-md-4 col-lg-3 mb-2"
                >
                    <div className="components-container p-2">
                        <div className="d-flex">
                            <div
                                className="live-component-img d-flex justify-content-center align-items-center"
                                style={{background: item.gradient}}>
                                <img width="17px" height="17px"
                                     src={item.imageUrl}
                                     alt={`Image for the ${item.humanName} UX package`}
                                />
                            </div>
                            <h4 className="ubuntu-title ps-2 align-self-center">
                                {item.humanName}
                            </h4>
                        </div>
                    </div>
                </a>
            ))}
        </div>
    );
}
